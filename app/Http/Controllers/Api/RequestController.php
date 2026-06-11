<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\PickupRequest;
use App\Models\RequestStatusLog;
use App\Services\RequestStatusTransitionService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    use ApiResponseTrait;

    /**
     * Create a new request (Scrap/Corporate/Donation)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:scrap,donation',
            'address' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'scheduled_at' => 'required|date|after:now',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'customer_phone' => 'required|string',
            'customer_name' => 'required|string',
            'payout_method' => 'nullable|string',
            'items' => 'nullable|array', // For scrap/donation items
            'donation_category' => 'nullable|string',
        ]);

        $type = RequestType::tryFrom($validated['request_type']);

        // Auto-assign warehouse by city
        $warehouse = \App\Models\Warehouse::where('city_id', $validated['city_id'])
            ->where('status', true)
            ->first();

        // Create pickup request
        $pickupRequest = PickupRequest::create([
            'customer_id' => Auth::id(),
            'request_type' => $type->value,
            'status' => 'pending', // Old enum column - use legacy value
            'status_new' => RequestStatus::PENDING_WAREHOUSE->value, // New string column
            'address' => $validated['address'],
            'city_id' => $validated['city_id'],
            'warehouse_id' => $warehouse?->id,
            'warehouse_assigned_at' => $warehouse ? now() : null,
            'scheduled_at' => $validated['scheduled_at'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'customer_phone' => $validated['customer_phone'],
            'customer_name' => $validated['customer_name'],
            'payout_method' => $validated['payout_method'] ?? null,
            'donation_category' => $validated['donation_category'] ?? null,
            'pickup_code' => $this->generatePickupCode(),
            'estimated_amount' => 0,
        ]);

        if (!empty($validated['items']) && is_array($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $pickupRequest->items()->create([
                    'category_id' => $item['category_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'weight' => $item['weight'] ?? 0,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }
        }

        // Log status change
        RequestStatusLog::logStatusChange(
            $pickupRequest->id,
            null,
            RequestStatus::PENDING_WAREHOUSE->value,
            Auth::id(),
            'customer',
            'Request created'
        );

        return $this->successResponse('request.created', $this->formatRequestResponse($pickupRequest));
    }

    /**
     * Get request details with all related data
     */
    public function show($id)
    {
        $request = PickupRequest::with([
            'customer',
            'warehouse',
            'statusLogs.changedBy',
            'currentAssignment.pickupBoy',
            'latestEstimate',
            'items.category',
            'images',
            'warehouseReceivedBy',
            'assignedByUser',
        ])->findOrFail($id);

        // Authorization
        $user = Auth::user();
        if ($request->customer_id !== $user->id && !$user->hasAnyRole(['admin', 'warehouse', 'payment_admin'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        return $this->successResponse('request.fetched', $this->formatRequestResponse($request));
    }

    /**
     * List requests (role-based filtering)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PickupRequest::with([
            'customer',
            'warehouse',
            'currentAssignment.pickupBoy',
            'latestEstimate',
        ]);

        // Filter by role
        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        } elseif ($user->hasRole('warehouse')) {
            // Only own warehouse requests
            $warehouse = $user->warehouse;
            if ($warehouse) {
                $query->where('warehouse_id', $warehouse->id);
            } else {
                return $this->errorResponse('auth.no_warehouse_assigned', 403);
            }
        } elseif (!$user->hasAnyRole(['admin', 'payment_admin'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Filter by status
        if ($request->has('status')) {
            $status = RequestStatus::tryFrom($request->status);
            if ($status) {
                $query->where('status_new', $status->value);
            }
        }

        // Filter by type
        if ($request->has('request_type')) {
            $type = RequestType::tryFrom($request->request_type);
            if ($type) {
                $query->where('request_type', $type->value);
            }
        }

        // Filter by period
        if ($request->has('period')) {
            $period = $request->period;
            $query = $this->filterByPeriod($query, $period);
        }

        $requests = $query->latest()->paginate($request->per_page ?? 20)
            ->through(fn($r) => $this->formatRequestResponse($r));

        return $this->paginatedResponse('request.list_fetched', $requests);
    }

    /**
     * Update request (customer only, before pickup started)
     */
    public function update(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);

        // Only customer can update their request
        if ($pickupRequest->customer_id !== Auth::id()) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Can only update if not yet picked up
        $status = RequestStatus::tryFrom($pickupRequest->status_new ?? $pickupRequest->status);
        if (
            $status && !in_array($status, [
                RequestStatus::PENDING_WAREHOUSE,
                RequestStatus::ESTIMATE_PENDING,
                RequestStatus::ESTIMATE_SHARED,
                RequestStatus::ESTIMATE_APPROVED,
            ])
        ) {
            return $this->errorResponse('request.cannot_update_after_pickup', 400);
        }

        $validated = $request->validate([
            'address' => 'nullable|string',
            'scheduled_at' => 'nullable|date|after:now',
            'customer_phone' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $pickupRequest->update($validated);

        return $this->successResponse('request.updated', $this->formatRequestResponse($pickupRequest));
    }

    /**
     * Cancel request
     */
    public function cancel(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);

        // Only customer can cancel their request, and only before pickup started
        if ($pickupRequest->customer_id !== Auth::id()) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        if (!RequestStatusTransitionService::canBeCancelled($pickupRequest)) {
            return $this->errorResponse('request.cannot_cancel_after_pickup', 400);
        }

        $reason = $request->input('reason', 'Cancelled by customer');

        try {
            $pickupRequest->transitionTo(
                RequestStatus::CANCELLED,
                Auth::id(),
                'customer',
                $reason
            );

            return $this->successResponse('request.cancelled', $this->formatRequestResponse($pickupRequest));
        } catch (\Exception $e) {
            return $this->errorResponse('request.cancel_failed', 400, $e->getMessage());
        }
    }

    /**
     * Get status history
     */
    public function statusHistory($id)
    {
        $request = PickupRequest::findOrFail($id);

        // Authorization
        $user = Auth::user();
        if ($request->customer_id !== $user->id && !$user->hasAnyRole(['admin', 'warehouse', 'pickup_boy', 'payment_admin'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        $history = RequestStatusTransitionService::getStatusHistory($request);

        return $this->successResponse('request.status_history_fetched', [
            'request_id' => $request->id,
            'current_status' => $request->status_new ?? $request->status,
            'history' => $history,
        ]);
    }

    /**
     * Get next allowed actions
     */
    public function getNextActions($id)
    {
        $request = PickupRequest::findOrFail($id);

        // Authorization
        $user = Auth::user();
        $userRole = $user->getRoleNames()->first() ?? 'customer';

        $actions = RequestStatusTransitionService::getNextAllowedActions($request, $userRole);

        return $this->successResponse('request.next_actions_fetched', [
            'request_id' => $request->id,
            'current_status' => $request->status_new ?? $request->status,
            'next_actions' => $actions,
        ]);
    }

    /**
     * Format request response with all data
     */
    private function formatRequestResponse(PickupRequest $request): array
    {
        $status = RequestStatus::tryFrom($request->status_new ?? $request->status);
        $type = RequestType::tryFrom($request->request_type);

        return [
            'id' => $request->id,
            'request_type' => $request->request_type,
            'request_type_label' => $type?->label(),
            'status' => $request->status_new ?? $request->status,
            'status_label' => $status?->label(),
            'pickup_code' => $request->pickup_code,

            'customer' => $request->customer ? [
                'id' => $request->customer->id,
                'name' => $request->customer->name,
                'phone' => $request->customer->phone,
                'email' => $request->customer->email,
            ] : null,

            'warehouse' => $request->warehouse ? [
                'id' => $request->warehouse->id,
                'name' => $request->warehouse->name,
                'location' => $request->warehouse->address,
            ] : null,

            'pickup_boy' => $request->currentAssignment?->pickupBoy ? [
                'id' => $request->currentAssignment->pickupBoy->id,
                'name' => $request->currentAssignment->pickupBoy->name,
                'phone' => $request->currentAssignment->pickupBoy->phone,
                'status' => $request->currentAssignment->status,
            ] : null,

            'estimate' => $request->latestEstimate ? [
                'id' => $request->latestEstimate->id,
                'amount' => $request->latestEstimate->estimated_amount,
                'status' => $request->latestEstimate->status,
                'created_at' => $request->latestEstimate->created_at,
            ] : null,

            'payment' => [
                'status' => $request->payment_status,
                'method' => $request->payment_method,
                'amount' => $request->final_amount,
                'reference' => $request->payment_reference,
            ],

            'timeline' => [
                'created_at' => $request->created_at?->format('Y-m-d H:i:s'),
                'warehouse_assigned_at' => $request->warehouse_assigned_at?->format('Y-m-d H:i:s'),
                'pickup_started_at' => $request->pickup_started_at?->format('Y-m-d H:i:s'),
                'pickup_completed_at' => $request->pickup_completed_at?->format('Y-m-d H:i:s'),
                'warehouse_received_at' => $request->warehouse_received_at?->format('Y-m-d H:i:s'),
                'payment_pending_at' => $request->payment_pending_at?->format('Y-m-d H:i:s'),
                'payment_completed_at' => $request->payment_completed_at?->format('Y-m-d H:i:s'),
                'completed_at' => $request->completed_at?->format('Y-m-d H:i:s'),
            ],

            'next_allowed_actions' => RequestStatusTransitionService::getNextAllowedActions(
                $request,
                Auth::user()->getRoleNames()->first() ?? 'customer'
            ),

            'address' => $request->address,
            'city_id' => $request->city_id,
            'scheduled_at' => $request->scheduled_at?->format('Y-m-d H:i:s'),
            'estimated_amount' => $request->estimated_amount,
            'final_amount' => $request->final_amount,
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

    /**
     * Filter query by period
     */
    private function filterByPeriod($query, $period)
    {
        return match ($period) {
            'today' => $query->whereDate('created_at', now()->toDateString()),
            'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
            'year' => $query->whereYear('created_at', now()->year),
            default => $query,
        };
    }
}
