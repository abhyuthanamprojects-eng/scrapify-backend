<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\PickupRequest;
use App\Services\RequestStatusTransitionService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Create donation request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'scheduled_at' => 'required|date|after:now',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'customer_phone' => 'required|string',
            'customer_name' => 'required|string',
            'donation_category' => 'required|string',
            'items_description' => 'nullable|string|max:500',
        ]);

        try {
            // Auto-assign warehouse by city
            $warehouse = \App\Models\Warehouse::where('city_id', $validated['city_id'])
                ->where('status', true)
                ->first();

            // Create donation request (no payment required)
            $donation = PickupRequest::create([
                'customer_id' => Auth::id(),
                'request_type' => RequestType::DONATION->value,
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
                'donation_category' => $validated['donation_category'],
                'pickup_code' => $this->generatePickupCode(),
                'estimated_amount' => 0, // Donations have no payment
            ]);

            // Log status change
            \App\Models\RequestStatusLog::logStatusChange(
                $donation->id,
                null,
                RequestStatus::PENDING_WAREHOUSE->value,
                Auth::id(),
                'customer',
                'Donation request created'
            );

            event(new \App\Events\RequestCreated($donation));

            return $this->successResponse('donation.created', [
                'id' => $donation->id,
                'pickup_code' => $donation->pickup_code,
                'request_type' => 'donation',
                'status' => $donation->status_new,
                'status_label' => RequestStatus::tryFrom($donation->status_new)?->label(),
                'customer_name' => $donation->customer_name,
                'address' => $donation->address,
                'scheduled_at' => $donation->scheduled_at?->format('Y-m-d H:i:s'),
            ], 201);
        } catch (\Exception $e) {
            return $this->errorResponse('donation.creation_failed', 400, $e->getMessage());
        }
    }

    /**
     * List donations (filtered by role)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PickupRequest::where('request_type', RequestType::DONATION->value)
            ->with([
                'customer',
                'currentAssignment.pickupBoy',
            ]);

        // Filter by role
        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        } elseif (!$user->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Filter by status
        if ($request->has('status')) {
            $status = RequestStatus::tryFrom($request->status);
            if ($status) {
                $query->where('status_new', $status->value);
            }
        }

        // Filter by period
        if ($request->has('period')) {
            $query = $this->filterByPeriod($query, $request->period);
        }

        $donations = $query->latest()->paginate($request->per_page ?? 20)
            ->through(fn($d) => $this->formatDonationResponse($d));

        return $this->paginatedResponse('donation.list_fetched', $donations);
    }

    /**
     * Get donation details
     */
    public function show($id)
    {
        $donation = PickupRequest::where('request_type', RequestType::DONATION->value)
            ->with([
                'customer',
                'currentAssignment.pickupBoy',
                'statusLogs.changedBy',
            ])
            ->findOrFail($id);

        // Authorization
        $user = Auth::user();
        if ($donation->customer_id !== $user->id && !$user->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        return $this->successResponse('donation.fetched', $this->formatDonationDetailResponse($donation));
    }

    /**
     * Cancel donation
     */
    public function cancel(Request $request, $id)
    {
        $donation = PickupRequest::where('request_type', RequestType::DONATION->value)
            ->findOrFail($id);

        // Only customer can cancel
        if ($donation->customer_id !== Auth::id()) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Can only cancel before pickup started
        $status = RequestStatus::tryFrom($donation->status_new);
        if (!in_array($status, [
            RequestStatus::PENDING_WAREHOUSE,
            RequestStatus::PICKUP_BOY_ASSIGNED,
        ])) {
            return $this->errorResponse('donation.cannot_cancel_after_pickup', 400);
        }

        $reason = $request->input('reason', 'Cancelled by customer');

        try {
            $donation->transitionTo(
                RequestStatus::CANCELLED,
                Auth::id(),
                'customer',
                $reason
            );

            return $this->successResponse('donation.cancelled', [
                'request_id' => $donation->id,
                'status' => RequestStatus::CANCELLED->value,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('donation.cancel_failed', 400, $e->getMessage());
        }
    }

    /**
     * Get donation statistics (warehouse dashboard)
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        $query = PickupRequest::where('request_type', RequestType::DONATION->value);

        // Filter by warehouse if user is warehouse
        if ($user->hasRole('warehouse') && $user->warehouse) {
            $query->where('warehouse_id', $user->warehouse->id);
        }

        return $this->successResponse('donation.statistics_fetched', [
            'total' => (clone $query)->count(),
            'pending_warehouse' => (clone $query)->where('status_new', RequestStatus::PENDING_WAREHOUSE->value)->count(),
            'assigned' => (clone $query)->where('status_new', RequestStatus::PICKUP_BOY_ASSIGNED->value)->count(),
            'in_progress' => (clone $query)->where('status_new', RequestStatus::PICKUP_STARTED->value)->count(),
            'warehouse_received' => (clone $query)->where('status_new', RequestStatus::WAREHOUSE_RECEIVED->value)->count(),
            'completed' => (clone $query)->where('status_new', RequestStatus::COMPLETED->value)->count(),
            'cancelled' => (clone $query)->where('status_new', RequestStatus::CANCELLED->value)->count(),
        ]);
    }

    /**
     * Format donation response
     */
    private function formatDonationResponse(PickupRequest $donation): array
    {
        $status = RequestStatus::tryFrom($donation->status_new);

        return [
            'id' => $donation->id,
            'pickup_code' => $donation->pickup_code,
            'request_type' => 'donation',
            'status' => $donation->status_new,
            'status_label' => $status?->label(),
            'customer_name' => $donation->customer_name,
            'customer_phone' => $donation->customer_phone,
            'address' => $donation->address,
            'scheduled_at' => $donation->scheduled_at?->format('Y-m-d H:i:s'),
            'donation_category' => $donation->donation_category,
            'pickup_boy' => $donation->currentAssignment?->pickupBoy ? [
                'id' => $donation->currentAssignment->pickupBoy->id,
                'name' => $donation->currentAssignment->pickupBoy->name,
            ] : null,
        ];
    }

    /**
     * Format detailed donation response
     */
    private function formatDonationDetailResponse(PickupRequest $donation): array
    {
        $status = RequestStatus::tryFrom($donation->status_new);

        return [
            'id' => $donation->id,
            'pickup_code' => $donation->pickup_code,
            'request_type' => 'donation',
            'status' => $donation->status_new,
            'status_label' => $status?->label(),
            'customer' => [
                'id' => $donation->customer->id,
                'name' => $donation->customer->name,
                'phone' => $donation->customer->phone,
                'email' => $donation->customer->email,
            ],
            'address' => $donation->address,
            'latitude' => $donation->latitude,
            'longitude' => $donation->longitude,
            'scheduled_at' => $donation->scheduled_at?->format('Y-m-d H:i:s'),
            'donation_category' => $donation->donation_category,
            'pickup_boy' => $donation->currentAssignment?->pickupBoy ? [
                'id' => $donation->currentAssignment->pickupBoy->id,
                'name' => $donation->currentAssignment->pickupBoy->name,
                'phone' => $donation->currentAssignment->pickupBoy->phone,
            ] : null,
            'timeline' => [
                'created_at' => $donation->created_at?->format('Y-m-d H:i:s'),
                'warehouse_assigned_at' => $donation->warehouse_assigned_at?->format('Y-m-d H:i:s'),
                'pickup_started_at' => $donation->pickup_started_at?->format('Y-m-d H:i:s'),
                'pickup_completed_at' => $donation->pickup_completed_at?->format('Y-m-d H:i:s'),
                'warehouse_received_at' => $donation->warehouse_received_at?->format('Y-m-d H:i:s'),
                'completed_at' => $donation->completed_at?->format('Y-m-d H:i:s'),
            ],
            'status_history' => $donation->statusLogs->map(fn($log) => [
                'old_status' => $log->old_status,
                'new_status' => $log->new_status,
                'changed_by' => $log->changedBy?->name,
                'changed_by_role' => $log->changed_by_role,
                'notes' => $log->notes,
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
            ]),
        ];
    }

    /**
     * Generate unique pickup code
     */
    private function generatePickupCode(): string
    {
        $prefix = 'DON';
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
