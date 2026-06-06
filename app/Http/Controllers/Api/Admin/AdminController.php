<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Models\Payment;
use App\Models\User;
use App\Models\Assignment;
use App\Models\PickupRequest;
use App\Models\PickupAssignmentHistory;
use App\Models\PickupStatusLog;
use App\Models\PickupBoyLocation;
use App\Models\Withdrawal;
use App\Services\ActivityLogger;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;


class AdminController extends Controller
{
    use ApiResponseTrait;

    /**
     * View Activity Logs.
     */
    #[OA\Get(
        path: "/api/admin/logs",
        operationId: "getAdminLogs",
        tags: ["Admin"],
        summary: "View system activity logs",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Logs fetched")
        ]
    )]
    public function logs(Request $request)
    {
        $query = Activity::with('causer')->latest();

        if ($request->has('module')) {
            $query->where('properties->module', $request->module);
        }

        if ($request->has('action')) {
            $query->where('properties->action', $request->action);
        }

        if ($request->has('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        $logs = $query->paginate(20);

        return $this->successResponse('general.success', $logs);
    }

    /**
     * Verify KYC Document.
     */
    #[OA\Post(
        path: "/api/admin/kyc/{id}/verify",
        operationId: "verifyKycDocument",
        tags: ["Admin"],
        summary: "Approve or Reject KYC document",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["verified", "rejected"]),
                    new OA\Property(property: "rejection_reason", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "KYC Verified/Rejected")
        ]
    )]
    public function verifyKyc(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:status,rejected|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $kyc = KycDocument::find($id);

        if (!$kyc) {
            return $this->errorResponse('general.not_found', 404);
        }

        $kyc->status = $request->status;
        if ($request->status === 'rejected') {
            $kyc->rejection_reason = $request->rejection_reason;
        }
        $kyc->save();

        ActivityLogger::log('verify_kyc', 'admin', 'KYC document ' . $request->status, ['kyc_id' => $id, 'status' => $request->status]);

        return $this->successResponse('kyc.' . ($request->status === 'verified' ? 'approved' : 'rejected'), $kyc);
    }

    #[OA\Post(
        path: "/api/admin/payments/{id}/approve",
        operationId: "approvePayment",
        tags: ["Admin"],
        summary: "Approve or Reject Payment",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["approved", "failed"], example: "approved"),
                    new OA\Property(property: "remarks", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Payment status updated"
            )
        ]
    )]
    public function approvePayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,failed',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $payment = Payment::find($id);

        if (!$payment) {
            return $this->errorResponse('general.not_found', 404);
        }

        $payment->status = $request->status;
        if ($request->has('remarks')) {
            $payment->remarks = $request->remarks;
        }
        $payment->save();

        ActivityLogger::log('approve_payment', 'admin', 'Payment ' . $request->status, ['payment_id' => $id, 'status' => $request->status]);

        if ($request->status === 'approved' && $payment->pickup_request_id) {
            $payment->pickupRequest()->update(['status' => 'completed']);
        }

        return $this->successResponse('payment.' . ($request->status === 'approved' ? 'approved' : 'failed'), $payment);
    }
    #[OA\Post(
        path: "/api/admin/pickups/{id}/assign",
        operationId: "assignPickup",
        tags: ["Admin"],
        summary: "Assign pickup to pickup boy",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["pickup_boy_id"],
                properties: [
                    new OA\Property(property: "pickup_boy_id", type: "integer"),
                    new OA\Property(property: "notes", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Pickup Assigned"
            )
        ]
    )]
    public function assignPickup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pickup_boy_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $pickupRequest = \App\Models\PickupRequest::find($id);

        if (!$pickupRequest) {
            return $this->errorResponse('pickup.not_found', 404);
        }

        if ($pickupRequest->request_type === 'corporate' && $pickupRequest->estimated_amount === null) {
            return $this->errorResponse('corporate.quote_required_before_assignment', 422);
        }

        // Check if user is pickup boy?
        $pickupBoy = \App\Models\User::find($request->pickup_boy_id);
        if (!$pickupBoy->hasRole('pickup_boy')) {
            return $this->errorResponse('admin.user_not_pickup_boy', 400);
        }

        // Create Assignment
        $assignment = \App\Models\Assignment::create([
            'pickup_request_id' => $id,
            'pickup_boy_id' => $request->pickup_boy_id,
            'status' => 'assigned',
            'notes' => $request->notes,
        ]);

        $pickupRequest->update(['status' => 'assigned']);

        ActivityLogger::log('assign_pickup', 'admin', 'Pickup assigned to ' . $pickupBoy->name, ['pickup_id' => $id, 'pickup_boy_id' => $request->pickup_boy_id]);

        return $this->successResponse('pickup.assigned_success', $assignment);
    }

    /**
     * List all pickup requests (Admin).
     */
    #[OA\Get(
        path: "/api/admin/pickups",
        operationId: "adminListPickups",
        tags: ["Admin"],
        summary: "List all pickup requests with global filters",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Pickups fetched")
        ]
    )]
    public function listPickups(Request $request)
    {
        $query = \App\Models\PickupRequest::with(['customer', 'assignment.pickupBoy', 'items.category']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        $pickups = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginatedResponse('admin.pickups_fetched', $pickups);
    }

    public function getPickup($id)
    {
        $pickup = \App\Models\PickupRequest::with(['customer', 'assignment.pickupBoy', 'items.category', 'images', 'statusLogs'])->find($id);

        if (!$pickup) {
            return $this->errorResponse('pickup.not_found', 404);
        }

        return $this->successResponse('admin.pickup_fetched', $pickup);
    }

    public function updatePickupStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $pickup = \App\Models\PickupRequest::findOrFail($id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($pickup, $request) {
            $pickup->update(['status' => $request->status]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => $request->status,
                'notes' => $request->notes ?? 'Status updated by admin',
                'created_by' => auth()->id()
            ]);

            // Sync assignment if relevant
            $assignment = $pickup->assignment;
            if ($assignment) {
                $assignment->update(['status' => $request->status]);
            }
        });

        return $this->successResponse('admin.pickup_status_updated', $pickup);
    }

    public function getRescheduleRequests($id)
    {
        $pickup = \App\Models\PickupRequest::findOrFail($id);

        if ($pickup->status !== 'reschedule_requested') {
            return $this->errorResponse('admin.no_reschedule_requested', 400);
        }

        return $this->successResponse('admin.reschedule_request_fetched', [
            'pickup_request_id' => $pickup->id,
            'reschedule_reason' => $pickup->reschedule_reason,
            'current_scheduled_at' => $pickup->scheduled_at ? $pickup->scheduled_at->format('Y-m-d H:i:s') : null,
            'assignment' => $pickup->assignment ? $pickup->assignment->load('pickupBoy') : null
        ]);
    }

    public function approveReschedule(Request $request, $id)
    {
        $request->validate([
            'new_scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string'
        ]);

        $pickup = \App\Models\PickupRequest::findOrFail($id);

        if ($pickup->status !== 'reschedule_requested') {
            return $this->errorResponse('admin.no_reschedule_requested', 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($pickup, $request) {
            $pickup->update([
                'status' => 'rescheduled',
                'scheduled_at' => $request->new_scheduled_at
            ]);

            if ($pickup->assignment) {
                $pickup->assignment->update(['status' => 'rescheduled']);
            }

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'rescheduled',
                'notes' => 'Admin approved reschedule. Details: ' . $request->notes,
                'created_by' => auth()->id()
            ]);

        });

        return $this->successResponse('admin.reschedule_approved', $pickup);
    }

    public function rejectReschedule(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string'
        ]);

        $pickup = \App\Models\PickupRequest::findOrFail($id);

        if ($pickup->status !== 'reschedule_requested') {
            return $this->errorResponse('admin.no_reschedule_requested', 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($pickup, $request) {
            // Revert back to accepted/assigned or specific state? Let's assume 'assigned' or previous valid state
            // Often if rejected, you keep the original schedule and it goes back to 'assigned' or 'pending'
            $pickup->update([
                'status' => 'assigned'
            ]);

            if ($pickup->assignment) {
                $pickup->assignment->update(['status' => 'assigned']);
            }

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'reschedule_rejected',
                'notes' => 'Admin rejected reschedule request. Details: ' . $request->notes,
                'created_by' => auth()->id()
            ]);
        });

        return $this->successResponse('admin.reschedule_rejected', $pickup);
    }

    /**
     * List all pickup boys (Agents).
     */
    #[OA\Get(
        path: "/api/admin/pickup-boys",
        operationId: "adminListAgents",
        tags: ["Admin"],
        summary: "List all pickup agents",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Agents fetched")
        ]
    )]
    public function listPickupBoys(Request $request)
    {
        $query = \App\Models\User::role('pickup_boy')
            ->with(['city', 'warehouse'])
            ->withCount([
                'assignments as assigned_pickups_count',
                'assignments as completed_pickups_count' => function ($q) {
                    $q->where('status', 'completed');
                }
            ]);

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('is_online')) {
            $query->where('is_online', $request->is_online);
        }

        if ($request->has('is_available')) {
            $query->where('is_available', $request->is_available);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $agents = $query->get();

        return $this->successResponse('admin.agents_fetched', $agents);
    }

    public function getPickupBoy($id)
    {
        $agent = \App\Models\User::role('pickup_boy')
            ->with(['city', 'warehouse'])
            ->withCount([
                'assignments as assigned_pickups_count',
                'assignments as completed_pickups_count' => function ($q) {
                    $q->where('status', 'completed');
                }
            ])
            ->findOrFail($id);

        return $this->successResponse('admin.agent_fetched', $agent);
    }

    public function togglePickupBoyStatus(Request $request, $id)
    {
        $agent = \App\Models\User::role('pickup_boy')->findOrFail($id);

        $updates = [];
        if ($request->has('status'))
            $updates['status'] = $request->status;
        if ($request->has('is_available'))
            $updates['is_available'] = $request->is_available;

        if (!empty($updates)) {
            $agent->update($updates);
        }

        return $this->successResponse('admin.agent_status_updated', $agent);
    }

    public function getPickupBoyPickups($id)
    {
        $pickups = \App\Models\Assignment::where('pickup_boy_id', $id)
            ->with(['pickupRequest.customer', 'pickupRequest.items.category'])
            ->latest('assigned_at')
            ->paginate(20);

        return $this->paginatedResponse('admin.agent_pickups_fetched', $pickups);
    }

    public function getPickupBoyTracking($id)
    {
        $agent = \App\Models\User::role('pickup_boy')->findOrFail($id);
        $history = \App\Models\PickupBoyLocation::where('pickup_boy_id', $id)
            ->latest()
            ->limit(50)
            ->get();

        return $this->successResponse('admin.agent_tracking_fetched', [
            'current_location' => [
                'latitude' => $agent->latitude,
                'longitude' => $agent->longitude,
                'updated_at' => $agent->location_updated_at ? $agent->location_updated_at->format('Y-m-d H:i:s') : null,
            ],
            'history' => $history
        ]);
    }

    public function getPickupTracking($id)
    {
        $pickupRequest = \App\Models\PickupRequest::with(['statusLogs.creator'])->findOrFail($id);

        $trackingData = [
            'pickup_request_id' => $pickupRequest->id,
            'order_code' => $pickupRequest->pickup_code,
            'current_status' => $pickupRequest->status,
            'scheduled_time' => $pickupRequest->scheduled_at ? $pickupRequest->scheduled_at->format('Y-m-d H:i:s') : null,
            'logs' => $pickupRequest->statusLogs,
        ];

        // Include assigned pickup boy's current live location if applicable
        $assignment = \App\Models\Assignment::where('pickup_request_id', $id)->first();
        if ($assignment && $assignment->pickupBoy) {
            $trackingData['pickup_boy'] = [
                'id' => $assignment->pickupBoy->id,
                'name' => $assignment->pickupBoy->name,
                'phone' => $assignment->pickupBoy->phone,
            ];
            $trackingData['pickup_boy_location'] = [
                'latitude' => $assignment->pickupBoy->latitude,
                'longitude' => $assignment->pickupBoy->longitude,
                'last_updated' => $assignment->pickupBoy->location_updated_at ? $assignment->pickupBoy->location_updated_at->format('Y-m-d H:i:s') : null,
            ];
        }

        return $this->successResponse('admin.pickup_tracking_fetched', $trackingData);
    }

    public function reassignPickup(Request $request, $id)
    {
        $request->validate([
            'pickup_boy_id' => 'required|exists:users,id',
            'reason' => 'nullable|string'
        ]);

        $pickupRequest = \App\Models\PickupRequest::findOrFail($id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($pickupRequest, $request) {
            // Get old assignment
            $oldAssignment = \App\Models\Assignment::where('pickup_request_id', $pickupRequest->id)
                ->where('status', '!=', 'completed')
                ->first();

            $oldPickupBoyId = $oldAssignment ? $oldAssignment->pickup_boy_id : null;

            if ($oldAssignment) {
                // Cancel previous assignments
                \App\Models\Assignment::where('pickup_request_id', $pickupRequest->id)
                    ->where('status', '!=', 'completed')
                    ->update(['status' => 'cancelled']);
            }

            // Create new assignment
            \App\Models\Assignment::create([
                'pickup_request_id' => $pickupRequest->id,
                'pickup_boy_id' => $request->pickup_boy_id,
                'status' => 'assigned',
                'assigned_at' => now()
            ]);

            $pickupRequest->update(['status' => 'assigned']);

            \App\Models\PickupAssignmentHistory::create([
                'pickup_request_id' => $pickupRequest->id,
                'old_pickup_boy_id' => $oldPickupBoyId,
                'new_pickup_boy_id' => $request->pickup_boy_id,
                'assigned_by_user_id' => auth()->id(),
                'reason' => $request->reason ?? 'Reassigned by admin',
            ]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickupRequest->id,
                'status' => 'reassigned',
                'notes' => 'Pickup reassigned by admin. Reason: ' . ($request->reason ?? 'N/A'),
                'created_by' => auth()->id()
            ]);

            // Notification dispatch could go here.
        });

        return $this->successResponse('admin.pickup_reassigned');
    }

    public function getPickupTimeline($id)
    {
        $logs = \App\Models\PickupStatusLog::where('pickup_request_id', $id)
            ->with('creator')
            ->latest()
            ->get();
        return $this->successResponse('admin.timeline_fetched', $logs);
    }

    public function getAssignmentHistory($id)
    {
        $history = \App\Models\PickupAssignmentHistory::where('pickup_request_id', $id)
            ->with(['oldPickupBoy', 'newPickupBoy', 'assignedByUser'])
            ->latest()
            ->get();

        return $this->successResponse('admin.assignment_history_fetched', $history);
    }

    public function getVerificationAudit($id)
    {
        $pickup = \App\Models\PickupRequest::with(['items.category', 'images' => fn($q) => $q->where('type', 'verification')])
            ->findOrFail($id);

        return $this->successResponse('admin.verification_audit_fetched', [
            'items' => $pickup->items,
            'images' => $pickup->images,
            'final_amount' => $pickup->final_amount,
            'notes' => \App\Models\PickupStatusLog::where('pickup_request_id', $id)->where('status', 'picked_up')->first()?->notes
        ]);
    }

    /**
     * List payments / settlements.
     */
    #[OA\Get(
        path: "/api/admin/payments",
        operationId: "adminListPayments",
        tags: ["Admin"],
        summary: "List all payments and settlements",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Payments fetched")
        ]
    )]
    public function listPayments(Request $request)
    {
        $query = \App\Models\Payment::with(['user', 'pickupRequest']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginatedResponse('admin.payments_fetched', $payments);
    }

    /**
     * Get single payment details.
     */
    #[OA\Get(
        path: "/api/admin/payments/{id}",
        operationId: "adminGetPaymentDetails",
        tags: ["Admin"],
        summary: "Get specific payment details",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Payment details fetched")
        ]
    )]
    public function getPayment($id)
    {
        $payment = Payment::with(['user', 'pickupRequest.items.category'])->find($id);

        if (!$payment) {
            return $this->errorResponse('general.not_found', 404);
        }

        return $this->successResponse('admin.payment_fetched', $payment);
    }

    /**
     * Approve/Reject a withdrawal request.
     */
    #[OA\Post(
        path: "/api/admin/withdrawals/{id}/approve",
        operationId: "adminApproveWithdrawal",
        tags: ["Admin"],
        summary: "Approve or Reject a withdrawal request",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["approved", "paid", "failed", "rejected"]),
                    new OA\Property(property: "transaction_id", type: "string"),
                    new OA\Property(property: "admin_notes", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Withdrawal Updated")
        ]
    )]
    public function approveWithdrawal(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,paid,failed,rejected',
            'transaction_id' => 'required_if:status,paid|string',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $withdrawal = \App\Models\Withdrawal::find($id);

        if (!$withdrawal) {
            return $this->errorResponse('general.not_found', 404);
        }

        $withdrawal->update([
            'status' => $request->status,
            'transaction_id' => $request->transaction_id ?? $withdrawal->transaction_id,
            'admin_notes' => $request->admin_notes ?? $withdrawal->admin_notes,
        ]);

        ActivityLogger::log('withdrawal_update', 'admin', 'Withdrawal ' . $request->status, ['withdrawal_id' => $id, 'status' => $request->status]);

        return $this->successResponse('withdrawal.updated', $withdrawal);
    }
}
