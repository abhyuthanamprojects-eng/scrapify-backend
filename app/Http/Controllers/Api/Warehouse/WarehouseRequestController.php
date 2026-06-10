<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Enums\RequestStatus;
use App\Models\PickupRequest;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Payment;
use App\Services\RequestStatusTransitionService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseRequestController extends Controller
{
    use ApiResponseTrait;

    private function getWarehouse($user)
    {
        if ($user->warehouse_id) {
            $warehouse = \App\Models\Warehouse::find($user->warehouse_id);
            if ($warehouse) {
                return $warehouse;
            }
        }

        $warehouse = \App\Models\Warehouse::where('manager_id', $user->id)->first();
        if ($warehouse) {
            return $warehouse;
        }

        if ($user->channel_partner_id) {
            $warehouse = \App\Models\Warehouse::where('channel_partner_id', $user->channel_partner_id)->first();
            if ($warehouse) {
                return $warehouse;
            }
        }

        return null;
    }

    /**
     * Get warehouse requests (filtered by warehouse)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        if (!$warehouse) {
            return $this->errorResponse('warehouse.not_assigned', 403);
        }

        $query = PickupRequest::where('warehouse_id', $warehouse->id)
            ->with([
                'customer',
                'currentAssignment.pickupBoy',
                'latestEstimate',
            ]);

        // Filter by status
        if ($request->has('status')) {
            $status = RequestStatus::tryFrom($request->status);
            if ($status) {
                $query->where('status_new', $status->value);
            }
        }

        // Filter by request type
        if ($request->has('request_type')) {
            $query->where('request_type', $request->request_type);
        }

        $requests = $query->latest()->paginate($request->per_page ?? 20)
            ->through(fn($r) => $this->formatWarehouseRequestResponse($r));

        return $this->paginatedResponse('warehouse.requests_fetched', $requests);
    }

    /**
     * Assign pickup boy to request
     */
    public function assignPickupBoy(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        // Verify warehouse
        if ($pickupRequest->warehouse_id !== $warehouse->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Validate request is in correct status for assignment
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if (!in_array($status, [
            RequestStatus::PENDING_WAREHOUSE,
            RequestStatus::ESTIMATE_APPROVED,
        ])) {
            return $this->errorResponse('warehouse.invalid_status_for_assignment', 400);
        }

        // For corporate, ensure estimate is approved
        if ($pickupRequest->isCorporate() && !RequestStatusTransitionService::isEstimateApprovedForPickup($pickupRequest)) {
            return $this->errorResponse('warehouse.estimate_not_approved', 400);
        }

        $validated = $request->validate([
            'pickup_boy_id' => 'required|exists:users,id',
        ]);

        $pickupBoy = User::findOrFail($validated['pickup_boy_id']);
        if (!$pickupBoy->hasRole('pickup_boy')) {
            return $this->errorResponse('warehouse.invalid_pickup_boy', 422);
        }

        try {
            // Create assignment
            $assignment = Assignment::create([
                'pickup_request_id' => $pickupRequest->id,
                'pickup_boy_id' => $pickupBoy->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            // Transition request status
            $pickupRequest->transitionTo(
                RequestStatus::PICKUP_BOY_ASSIGNED,
                $user->id,
                'warehouse',
                "Assigned to {$pickupBoy->name}"
            );

            // Update request warehouse assignment
            $pickupRequest->update([
                'warehouse_assigned_at' => now(),
                'assigned_by' => $user->id,
            ]);

            // Fire event
            event(new \App\Events\PickupBoyAssigned($pickupRequest, $pickupBoy));

            return $this->successResponse('warehouse.pickup_boy_assigned', [
                'request_id' => $pickupRequest->id,
                'assignment' => $assignment,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('warehouse.assignment_failed', 400, $e->getMessage());
        }
    }

    /**
     * Reassign pickup boy
     */
    public function reassignPickupBoy(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        if ($pickupRequest->warehouse_id !== $warehouse->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Can only reassign before pickup started
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if (!in_array($status, [RequestStatus::PICKUP_BOY_ASSIGNED])) {
            return $this->errorResponse('warehouse.cannot_reassign_after_pickup_started', 400);
        }

        $validated = $request->validate([
            'pickup_boy_id' => 'required|exists:users,id',
        ]);

        try {
            $oldAssignment = $pickupRequest->currentAssignment;
            $oldAssignment->delete();

            $newPickupBoy = User::findOrFail($validated['pickup_boy_id']);
            Assignment::create([
                'pickup_request_id' => $pickupRequest->id,
                'pickup_boy_id' => $newPickupBoy->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            return $this->successResponse('warehouse.pickup_boy_reassigned', [
                'request_id' => $pickupRequest->id,
                'new_pickup_boy' => $newPickupBoy->name,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('warehouse.reassignment_failed', 400, $e->getMessage());
        }
    }

    /**
     * Confirm warehouse received items
     */
    public function confirmReceived(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        if ($pickupRequest->warehouse_id !== $warehouse->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be in warehouse_receive_pending status
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if ($status !== RequestStatus::WAREHOUSE_RECEIVE_PENDING) {
            return $this->errorResponse('warehouse.invalid_status_for_receipt', 400);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            // Determine next status based on request type
            $type = $pickupRequest->request_type;
            $nextStatus = ($type === 'donation')
                ? RequestStatus::COMPLETED
                : RequestStatus::PAYMENT_PENDING;

            $pickupRequest->transitionTo(
                RequestStatus::WAREHOUSE_RECEIVED,
                $user->id,
                'warehouse',
                $validated['notes'] ?? 'Items received and verified'
            );

            // Update warehouse received tracking
            $pickupRequest->update([
                'warehouse_received_at' => now(),
                'warehouse_received_by' => $user->id,
            ]);

            // Auto-transition for donation
            if ($nextStatus === RequestStatus::COMPLETED) {
                $pickupRequest->transitionTo(
                    $nextStatus,
                    $user->id,
                    'warehouse',
                    'Donation request completed'
                );
                $pickupRequest->update(['completed_at' => now()]);

                event(new \App\Events\DonationCompleted($pickupRequest));
            } else {
                event(new \App\Events\WarehouseReceived($pickupRequest));
            }

            return $this->successResponse('warehouse.items_confirmed', [
                'request_id' => $pickupRequest->id,
                'next_status' => $nextStatus->value,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('warehouse.confirmation_failed', 400, $e->getMessage());
        }
    }

    /**
     * Move to payment pending (for scrap/corporate after warehouse received)
     */
    public function moveToPaymentPending(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        if ($pickupRequest->warehouse_id !== $warehouse->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be in warehouse_received status
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if ($status !== RequestStatus::WAREHOUSE_RECEIVED) {
            return $this->errorResponse('warehouse.invalid_status_for_payment', 400);
        }

        // Only for scrap/corporate
        if (!in_array($pickupRequest->request_type, ['scrap', 'corporate'])) {
            return $this->errorResponse('warehouse.payment_not_applicable_for_donation', 400);
        }

        try {
            $pickupRequest->transitionTo(
                RequestStatus::PAYMENT_PENDING,
                $user->id,
                'warehouse',
                'Moved to payment pending'
            );

            event(new \App\Events\PaymentPending($pickupRequest));

            return $this->successResponse('warehouse.moved_to_payment_pending', [
                'request_id' => $pickupRequest->id,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('warehouse.payment_transition_failed', 400, $e->getMessage());
        }
    }

    /**
     * Warehouse dashboard counts
     */
    public function dashboard()
    {
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        if (!$warehouse) {
            return $this->errorResponse('warehouse.not_assigned', 403);
        }

        $query = PickupRequest::where('warehouse_id', $warehouse->id);

        return $this->successResponse('warehouse.dashboard_fetched', [
            'new_requests' => (clone $query)->where('status_new', RequestStatus::PENDING_WAREHOUSE->value)->count(),
            'pickup_assigned' => (clone $query)->where('status_new', RequestStatus::PICKUP_BOY_ASSIGNED->value)->count(),
            'pickup_completed_waiting' => (clone $query)->whereIn('status_new', [
                RequestStatus::PICKUP_COMPLETED->value,
                RequestStatus::WAREHOUSE_RECEIVE_PENDING->value,
            ])->count(),
            'warehouse_received' => (clone $query)->where('status_new', RequestStatus::WAREHOUSE_RECEIVED->value)->count(),
            'payment_pending' => (clone $query)->whereIn('status_new', [
                RequestStatus::PAYMENT_PENDING->value,
                RequestStatus::PAYMENT_PROCESSING->value,
            ])->count(),
            'completed' => (clone $query)->where('status_new', RequestStatus::COMPLETED->value)->count(),
            'estimate_pending' => (clone $query)->where('status_new', RequestStatus::ESTIMATE_PENDING->value)->count(),
        ]);
    }

    /**
     * Format request for warehouse view
     */
    private function formatWarehouseRequestResponse(PickupRequest $request): array
    {
        $status = RequestStatus::tryFrom($request->status_new);

        return [
            'id' => $request->id,
            'pickup_code' => $request->pickup_code,
            'request_type' => $request->request_type,
            'status' => $request->status_new,
            'status_label' => $status?->label(),
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'address' => $request->address,
            'scheduled_at' => $request->scheduled_at?->format('Y-m-d H:i:s'),
            'estimated_amount' => $request->estimated_amount,
            'final_amount' => $request->final_amount,
            'pickup_boy' => $request->currentAssignment?->pickupBoy ? [
                'id' => $request->currentAssignment->pickupBoy->id,
                'name' => $request->currentAssignment->pickupBoy->name,
            ] : null,
            'estimate' => $request->latestEstimate ? [
                'amount' => $request->latestEstimate->estimated_amount,
                'status' => $request->latestEstimate->status,
            ] : null,
            'next_allowed_actions' => RequestStatusTransitionService::getNextAllowedActions($request, 'warehouse'),
        ];
    }

    /**
     * Mark items as warehouse received
     */
    public function markAsReceived(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        // Verify warehouse
        if ($pickupRequest->warehouse_id !== $warehouse->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be in warehouse_receive_pending status
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if ($status !== RequestStatus::WAREHOUSE_RECEIVE_PENDING) {
            return $this->errorResponse('warehouse.invalid_status_for_receipt', 400);
        }

        $validated = $request->validate([
            'received_weight' => 'nullable|numeric|min:0',
            'received_items_count' => 'nullable|integer|min:0',
            'received_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // Update request with received data
            $pickupRequest->update([
                'warehouse_received_at' => now(),
                'warehouse_received_by' => $user->id,
                'metadata' => array_merge(
                    is_array($pickupRequest->metadata) ? $pickupRequest->metadata : json_decode($pickupRequest->metadata, true) ?? [],
                    [
                        'received_weight' => $validated['received_weight'] ?? null,
                        'received_items_count' => $validated['received_items_count'] ?? null,
                        'received_amount' => $validated['received_amount'] ?? null,
                        'received_notes' => $validated['notes'] ?? null,
                    ]
                ),
            ]);

            // Transition status
            $pickupRequest->transitionTo(
                RequestStatus::WAREHOUSE_RECEIVED,
                $user->id,
                'warehouse',
                'Items received and verified by warehouse'
            );

            return $this->successResponse('warehouse.items_received', [
                'request_id' => $pickupRequest->id,
                'status' => RequestStatus::WAREHOUSE_RECEIVED->value,
                'received_at' => $pickupRequest->warehouse_received_at?->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('warehouse.receipt_failed', 400, $e->getMessage());
        }
    }

    /**
     * Initiate payment for received items (only for scrap & corporate)
     */
    public function initiatePayment(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        // Verify warehouse
        if ($pickupRequest->warehouse_id !== $warehouse->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be warehouse_received
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if ($status !== RequestStatus::WAREHOUSE_RECEIVED) {
            return $this->errorResponse('warehouse.invalid_status_for_payment', 400);
        }

        // Only scrap & corporate require payment
        $type = $pickupRequest->request_type;
        if (!in_array($type, ['scrap', 'corporate'])) {
            return $this->errorResponse('warehouse.no_payment_required', 400);
        }

        $validated = $request->validate([
            'type' => 'required|in:upi,bank_transfer,cash,wallet',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            // Create payment record
            $payment = Payment::create([
                'user_id' => $pickupRequest->customer_id,
                'pickup_request_id' => $pickupRequest->id,
                'amount' => $validated['amount'],
                'type' => $validated['type'],
                'status' => 'pending',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // Transition to payment pending
            $pickupRequest->transitionTo(
                RequestStatus::PAYMENT_PENDING,
                $user->id,
                'warehouse',
                "Payment initiated: {$validated['type']}"
            );

            return $this->successResponse('warehouse.payment_initiated', [
                'request_id' => $pickupRequest->id,
                'payment_id' => $payment->id,
                'amount' => $validated['amount'],
                'type' => $validated['type'],
                'status' => 'pending',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('warehouse.payment_initiation_failed', 400, $e->getMessage());
        }
    }

    /**
     * Confirm payment received
     */
    public function confirmPayment(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $user = Auth::user();
        $warehouse = $this->getWarehouse($user);

        // Verify warehouse
        if ($pickupRequest->warehouse_id !== $warehouse->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be payment_pending
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if (!in_array($status, [RequestStatus::PAYMENT_PENDING, RequestStatus::PAYMENT_PROCESSING])) {
            return $this->errorResponse('warehouse.invalid_status_for_confirmation', 400);
        }

        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'transaction_id' => 'nullable|string|max:100',
            'status' => 'required|in:completed,approved',
        ]);

        try {
            $payment = Payment::findOrFail($validated['payment_id']);

            // Verify payment belongs to this request
            if ($payment->pickup_request_id !== $pickupRequest->id) {
                return $this->errorResponse('auth.unauthorized', 403);
            }

            // Update payment
            $payment->update([
                'status' => $validated['status'],
                'transaction_id' => $validated['transaction_id'] ?? null,
            ]);

            // Transition to completed
            $pickupRequest->transitionTo(
                RequestStatus::COMPLETED,
                $user->id,
                'warehouse',
                "Payment {$validated['status']} - request completed"
            );

            return $this->successResponse('warehouse.payment_confirmed', [
                'request_id' => $pickupRequest->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'transaction_id' => $payment->transaction_id,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('warehouse.payment_confirmation_failed', 400, $e->getMessage());
        }
    }
}
