<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\PickupRequest;
use App\Models\CorporateBookingEstimate;
use App\Services\RequestStatusTransitionService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CorporateBookingController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get corporate booking options (categories, meeting types, sub-categories)
     */
    public function options()
    {
        // Get corporate-enabled category types with their children
        $categories = \App\Models\CategoryType::where('show_in_corporate_booking', true)
            ->where('status', true)
            ->with('categories.children')
            ->get()
            ->map(fn($catType) => [
                'id' => $catType->id,
                'name' => $catType->getTranslatedName(),
                'slug' => $catType->slug,
                'items' => $catType->categories()
                    ->where('status', true)
                    ->whereNull('parent_id')
                    ->with('children')
                    ->get()
                    ->map(fn($cat) => [
                        'id' => $cat->id,
                        'name' => $cat->getTranslatedName(),
                        'slug' => $cat->slug,
                        'children' => $cat->children()
                            ->where('status', true)
                            ->get()
                            ->map(fn($child) => [
                                'id' => $child->id,
                                'name' => $child->getTranslatedName(),
                                'slug' => $child->slug,
                            ])
                            ->values(),
                    ])
                    ->values(),
            ])
            ->values();

        return $this->successResponse('corporate.options_fetched', [
            'categories' => $categories,
            'meeting_types' => ['in_person', 'google_meet', 'skype'],
            'scrap_categories' => $categories,
        ]);
    }

    /**
     * Create a corporate booking
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_mobile' => 'required|string|max:20',
            'contact_email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'address_id' => 'nullable|exists:addresses,id',
            'city_id' => 'required|exists:cities,id',
            'pincode' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'scheduled_at' => 'required|date|after:now',
            'meeting_type' => 'required|in:in_person,google_meet,skype',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'corporate_categories' => 'nullable|array',
            'corporate_category_items' => 'nullable|array',
        ]);

        try {
            // Create pickup request (Warehouse will be assigned by admin later)
            $pickupRequest = PickupRequest::create([
                'customer_id' => Auth::id(),
                'request_type' => RequestType::CORPORATE->value,
                'status' => 'pending', // Old enum column - use legacy value
                'status_new' => RequestStatus::PENDING_WAREHOUSE->value, // New string column
                'address' => $validated['address'],
                'address_id' => $validated['address_id'] ?? null,
                'city_id' => $validated['city_id'],
                'warehouse_id' => null,
                'warehouse_assigned_at' => null,
                'pincode' => $validated['pincode'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'customer_name' => $validated['contact_name'],
                'customer_phone' => $validated['contact_mobile'],
                'customer_email' => $validated['contact_email'],
                'notes' => $validated['notes'] ?? null,
                'meeting_type' => $validated['meeting_type'],
                'pickup_code' => $this->generatePickupCode(),
                'estimated_amount' => 0,
                // Store corporate-specific data in JSON
                'metadata' => [
                    'company_name' => $validated['company_name'],
                    'contact_name' => $validated['contact_name'],
                    'contact_email' => $validated['contact_email'],
                    'meeting_type' => $validated['meeting_type'],
                    'corporate_categories' => $validated['corporate_categories'] ?? [],
                    'corporate_category_items' => $validated['corporate_category_items'] ?? [],
                ],
            ]);

            // Add items
            foreach ($validated['items'] as $item) {
                $pickupRequest->items()->create([
                    'category_id' => $item['category_id'],
                    'quantity' => $item['quantity'],
                    'weight' => $item['weight'] ?? null,
                ]);
            }

            // Log initial status
            \App\Models\RequestStatusLog::logStatusChange(
                $pickupRequest->id,
                null,
                RequestStatus::PENDING_WAREHOUSE->value,
                Auth::id(),
                'customer',
                'Corporate booking created'
            );

            // Transition to estimate_pending
            $pickupRequest->transitionTo(
                RequestStatus::ESTIMATE_PENDING,
                Auth::id(),
                'customer',
                'Corporate booking - estimate required'
            );

            return $this->successResponse('corporate.booking_created', $this->formatBookingResponse($pickupRequest->fresh(['customer', 'items'])));
        } catch (\Exception $e) {
            return $this->errorResponse('corporate.booking_creation_failed', 400, $e->getMessage());
        }
    }

    /**
     * List corporate bookings (filtered by role)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PickupRequest::where('request_type', RequestType::CORPORATE->value)
            ->with([
                'customer',
                'latestEstimate',
                'currentAssignment.pickupBoy',
            ]);

        // Filter by role
        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        } elseif (!$user->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Filter by estimate status
        if ($request->has('estimate_status')) {
            $estimateStatus = $request->estimate_status;
            $query->whereHas('latestEstimate', function ($q) use ($estimateStatus) {
                $q->where('status', $estimateStatus);
            });
        }

        // Filter by request status
        if ($request->has('status')) {
            $status = RequestStatus::tryFrom($request->status);
            if ($status) {
                $query->where('status_new', $status->value);
            }
        }

        $bookings = $query->latest()->paginate($request->per_page ?? 20)
            ->through(fn($b) => $this->formatBookingResponse($b));

        return $this->paginatedResponse('corporate.bookings_fetched', $bookings);
    }

    /**
     * Get corporate booking details
     */
    public function show($id)
    {
        $booking = PickupRequest::where('request_type', RequestType::CORPORATE->value)
            ->with([
                'customer',
                'latestEstimate',
                'currentAssignment.pickupBoy',
                'statusLogs',
            ])
            ->findOrFail($id);

        // Authorization
        $user = Auth::user();
        if ($booking->customer_id !== $user->id && !$user->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        return $this->successResponse('corporate.booking_fetched', $this->formatBookingResponse($booking));
    }

    /**
     * Create estimate for corporate booking
     */
    public function createEstimate(Request $request, $id)
    {
        $booking = PickupRequest::where('request_type', RequestType::CORPORATE->value)
            ->findOrFail($id);

        // Only warehouse can create estimate
        if (!Auth::user()->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be in estimate_pending or estimate_shared status
        $status = RequestStatus::tryFrom($booking->status_new);
        if (!in_array($status, [
            RequestStatus::ESTIMATE_PENDING,
            RequestStatus::ESTIMATE_SHARED,
        ])) {
            return $this->errorResponse('corporate.invalid_status_for_estimate', 400);
        }

        $validated = $request->validate([
            'estimated_amount' => 'required|numeric|min:0',
            'estimated_weight' => 'nullable|numeric|min:0',
            'estimated_items_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $estimate = CorporateBookingEstimate::create([
                'pickup_request_id' => $booking->id,
                'estimated_amount' => $validated['estimated_amount'],
                'estimated_weight' => $validated['estimated_weight'] ?? null,
                'estimated_items_count' => $validated['estimated_items_count'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            // Update request with estimated amount
            $booking->update(['estimated_amount' => $validated['estimated_amount']]);

            return $this->successResponse('corporate.estimate_created', [
                'estimate_id' => $estimate->id,
                'request_id' => $booking->id,
                'estimated_amount' => $estimate->estimated_amount,
                'status' => $estimate->status,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('corporate.estimate_creation_failed', 400, $e->getMessage());
        }
    }

    /**
     * Get latest estimate for booking
     */
    public function getEstimate($id)
    {
        $booking = PickupRequest::where('request_type', RequestType::CORPORATE->value)
            ->with('latestEstimate')
            ->findOrFail($id);

        // Authorization
        $user = Auth::user();
        if ($booking->customer_id !== $user->id && !$user->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        if (!$booking->latestEstimate) {
            return $this->errorResponse('corporate.estimate_not_found', 404);
        }

        return $this->successResponse('corporate.estimate_fetched', [
            'id' => $booking->latestEstimate->id,
            'request_id' => $booking->id,
            'estimated_amount' => $booking->latestEstimate->estimated_amount,
            'estimated_weight' => $booking->latestEstimate->estimated_weight,
            'estimated_items_count' => $booking->latestEstimate->estimated_items_count,
            'notes' => $booking->latestEstimate->notes,
            'status' => $booking->latestEstimate->status,
            'created_at' => $booking->latestEstimate->created_at?->format('Y-m-d H:i:s'),
            'approved_at' => $booking->latestEstimate->approved_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update estimate (only if pending)
     */
    public function updateEstimate(Request $request, $id)
    {
        $booking = PickupRequest::where('request_type', RequestType::CORPORATE->value)
            ->with('latestEstimate')
            ->findOrFail($id);

        // Only warehouse can update estimate
        if (!Auth::user()->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        if (!$booking->latestEstimate) {
            return $this->errorResponse('corporate.estimate_not_found', 404);
        }

        // Can only update if pending
        if ($booking->latestEstimate->status !== 'pending') {
            return $this->errorResponse('corporate.cannot_update_estimate', 400);
        }

        $validated = $request->validate([
            'estimated_amount' => 'required|numeric|min:0',
            'estimated_weight' => 'nullable|numeric|min:0',
            'estimated_items_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $booking->latestEstimate->update($validated);
            $booking->update(['estimated_amount' => $validated['estimated_amount']]);

            return $this->successResponse('corporate.estimate_updated', [
                'estimate_id' => $booking->latestEstimate->id,
                'estimated_amount' => $booking->latestEstimate->estimated_amount,
                'status' => $booking->latestEstimate->status,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('corporate.estimate_update_failed', 400, $e->getMessage());
        }
    }

    /**
     * Approve estimate (customer action)
     */
    public function approveEstimate(Request $request, $id)
    {
        $booking = PickupRequest::where('request_type', RequestType::CORPORATE->value)
            ->with('latestEstimate')
            ->findOrFail($id);

        // Only customer can approve
        if ($booking->customer_id !== Auth::id()) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        if (!$booking->latestEstimate) {
            return $this->errorResponse('corporate.estimate_not_found', 404);
        }

        // Can only approve if pending or shared
        if (!in_array($booking->latestEstimate->status, ['pending', 'shared'])) {
            return $this->errorResponse('corporate.cannot_approve_estimate', 400);
        }

        try {
            // Update estimate
            $booking->latestEstimate->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Transition request
            $booking->transitionTo(
                RequestStatus::ESTIMATE_APPROVED,
                Auth::id(),
                'customer',
                'Estimate approved by customer'
            );

            // Fire event
            event(new \App\Events\EstimateApproved($booking, $booking->latestEstimate));

            return $this->successResponse('corporate.estimate_approved', [
                'request_id' => $booking->id,
                'estimate_id' => $booking->latestEstimate->id,
                'status' => 'approved',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('corporate.estimate_approval_failed', 400, $e->getMessage());
        }
    }

    /**
     * Reject estimate (customer action)
     */
    public function rejectEstimate(Request $request, $id)
    {
        $booking = PickupRequest::where('request_type', RequestType::CORPORATE->value)
            ->with('latestEstimate')
            ->findOrFail($id);

        // Only customer can reject
        if ($booking->customer_id !== Auth::id()) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        if (!$booking->latestEstimate) {
            return $this->errorResponse('corporate.estimate_not_found', 404);
        }

        // Can only reject if pending or shared
        if (!in_array($booking->latestEstimate->status, ['pending', 'shared'])) {
            return $this->errorResponse('corporate.cannot_reject_estimate', 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            // Update estimate
            $booking->latestEstimate->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['reason'],
                'rejected_at' => now(),
            ]);

            // Transition request to cancelled
            $booking->transitionTo(
                RequestStatus::CANCELLED,
                Auth::id(),
                'customer',
                "Estimate rejected: {$validated['reason']}"
            );

            return $this->successResponse('corporate.estimate_rejected', [
                'request_id' => $booking->id,
                'estimate_id' => $booking->latestEstimate->id,
                'status' => 'rejected',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('corporate.estimate_rejection_failed', 400, $e->getMessage());
        }
    }

    /**
     * Share estimate with customer (warehouse action)
     */
    public function shareEstimate(Request $request, $id)
    {
        $booking = PickupRequest::where('request_type', RequestType::CORPORATE->value)
            ->with('latestEstimate')
            ->findOrFail($id);

        // Only warehouse can share
        if (!Auth::user()->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        if (!$booking->latestEstimate) {
            return $this->errorResponse('corporate.estimate_not_found', 404);
        }

        // Can only share if pending
        if ($booking->latestEstimate->status !== 'pending') {
            return $this->errorResponse('corporate.cannot_share_estimate', 400);
        }

        try {
            // Update estimate status
            $booking->latestEstimate->update(['status' => 'shared']);

            // Transition request
            $booking->transitionTo(
                RequestStatus::ESTIMATE_SHARED,
                Auth::id(),
                'warehouse',
                'Estimate shared with customer'
            );

            // Fire event
            event(new \App\Events\EstimateShared($booking, $booking->latestEstimate));

            return $this->successResponse('corporate.estimate_shared', [
                'request_id' => $booking->id,
                'estimate_id' => $booking->latestEstimate->id,
                'status' => 'shared',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('corporate.estimate_share_failed', 400, $e->getMessage());
        }
    }

    /**
     * Format booking response
     */
    private function formatBookingResponse(PickupRequest $booking): array
    {
        $status = RequestStatus::tryFrom($booking->status_new);

        return [
            'id' => $booking->id,
            'pickup_code' => $booking->pickup_code,
            'request_type' => 'corporate',
            'status' => $booking->status_new,
            'status_label' => $status?->label(),
            'customer_name' => $booking->customer_name,
            'customer_phone' => $booking->customer_phone,
            'address' => $booking->address,
            'scheduled_at' => $booking->scheduled_at?->format('Y-m-d H:i:s'),
            'estimated_amount' => $booking->estimated_amount,
            'final_amount' => $booking->final_amount,
            'estimate' => $booking->latestEstimate ? [
                'id' => $booking->latestEstimate->id,
                'amount' => $booking->latestEstimate->estimated_amount,
                'weight' => $booking->latestEstimate->estimated_weight,
                'items_count' => $booking->latestEstimate->estimated_items_count,
                'status' => $booking->latestEstimate->status,
                'notes' => $booking->latestEstimate->notes,
            ] : null,
            'pickup_boy' => $booking->currentAssignment?->pickupBoy ? [
                'id' => $booking->currentAssignment->pickupBoy->id,
                'name' => $booking->currentAssignment->pickupBoy->name,
            ] : null,
        ];
    }

    /**
     * Generate unique pickup code
     */
    private function generatePickupCode(): string
    {
        $prefix = 'SCR';
        $timestamp = now()->format('YmdHi');
        $random = strtoupper(substr(uniqid(), -4));
        return "{$prefix}-{$timestamp}-{$random}";
    }
}
