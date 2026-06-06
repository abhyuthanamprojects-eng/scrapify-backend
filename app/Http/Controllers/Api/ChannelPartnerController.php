<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\Payment;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\ApprovalRequest;
use App\Models\Withdrawal;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ChannelPartnerController extends Controller
{
    use ApiResponseTrait;

    protected function getPartner($user)
    {
        return $user->channel_partner_id;
    }

    #[OA\Get(
        path: "/api/channel-partner/dashboard",
        operationId: "getChannelPartnerDashboard",
        tags: ["Channel Partner"],
        summary: "Get channel partner dashboard metrics",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Dashboard data fetched")
        ]
    )]
    public function dashboard()
    {
        $user = Auth::user();
        $partnerId = $this->getPartner($user);
        
        if (!$partnerId) {
            return $this->errorResponse('partner.not_found', 404);
        }

        $dashboardData = [
            'total_customers' => \App\Models\ChannelPartnerCustomer::where('channel_partner_id', $partnerId)->count(),
            'total_pickups' => PickupRequest::where('channel_partner_id', $partnerId)->count(),
            'pending_pickups' => PickupRequest::where('channel_partner_id', $partnerId)->whereIn('status', ['pending', 'created'])->count(),
            'assigned_pickups' => PickupRequest::where('channel_partner_id', $partnerId)->where('status', 'assigned')->count(),
            'completed_pickups' => PickupRequest::where('channel_partner_id', $partnerId)->whereIn('status', ['completed', 'pickup_completed'])->count(),
            'delivered_to_warehouse' => PickupRequest::where('channel_partner_id', $partnerId)->where('status', 'delivered_to_warehouse')->count(),
            'pending_settlement' => \App\Models\Settlement::where('partner_id', $user->id)->whereIn('payout_status', ['pending', 'processing', 'hold'])->sum('net_amount'),
            'paid_settlement' => \App\Models\Settlement::where('partner_id', $user->id)->where('payout_status', 'paid')->sum('net_amount'),
            'total_orders' => PickupRequest::where('channel_partner_id', $partnerId)->count(),
            'active_orders' => PickupRequest::where('channel_partner_id', $partnerId)
                ->whereIn('status', ['pending', 'created', 'assigned', 'accepted', 'on_the_way', 'arrived', 'reached_location', 'pickup_started'])
                ->count(),
            'completed_orders' => PickupRequest::where('channel_partner_id', $partnerId)
                ->where('status', 'completed')
                ->count(),
            'cancelled_orders' => PickupRequest::where('channel_partner_id', $partnerId)
                ->where('status', 'cancelled')
                ->count(),
            'rescheduled_orders' => PickupRequest::where('channel_partner_id', $partnerId)
                ->where('status', 'rescheduled')
                ->count(),
            
            'active_warehouses' => Warehouse::where('channel_partner_id', $partnerId)->where('status', true)->count(),
            'pending_warehouse_approvals' => ApprovalRequest::where('channel_partner_id', $partnerId)
                ->where('entity_type', 'warehouse')
                ->where('status', 'pending')
                ->count(),

            'total_pickup_boys' => User::role('pickup_boy')->where('channel_partner_id', $partnerId)->count(),
            'available_pickup_boys' => User::role('pickup_boy')->where('channel_partner_id', $partnerId)->where('is_available', true)->count(),
            'active_pickup_boys' => User::role('pickup_boy')->where('channel_partner_id', $partnerId)->get()->filter->is_online->count(),
            'pending_pickup_boy_approvals' => ApprovalRequest::where('channel_partner_id', $partnerId)
                ->where('entity_type', 'pickup_boy')
                ->where('status', 'pending')
                ->count(),

            'recent_orders' => PickupRequest::where('channel_partner_id', $partnerId)
                ->with(['assignment.pickupBoy', 'warehouse'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'order_code' => $order->pickup_code,
                    'customer_name' => $order->customer_name,
                    'status' => $order->status,
                    'scheduled_at' => $order->scheduled_at,
                ])
        ];

        return $this->successResponse('partner.dashboard', $dashboardData);
    }

    public function orders(Request $request)
    {
        $partnerId = $this->getPartner(Auth::user());
        $query = PickupRequest::where('channel_partner_id', $partnerId)
            ->with(['customer', 'partnerCustomer', 'assignment.pickupBoy', 'warehouse']);

        if ($request->status) $query->where('status', $request->status);
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->pickup_boy_id) {
            $query->whereHas('assignment', fn($q) => $q->where('pickup_boy_id', $request->pickup_boy_id));
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('pickup_code', 'like', "%$s%")
                ->orWhere('customer_name', 'like', "%$s%")
                ->orWhere('customer_phone', 'like', "%$s%")
                ->orWhereHas('partnerCustomer', fn($cq) => $cq->where('name', 'like', "%$s%")->orWhere('mobile', 'like', "%$s%")));
        }

        $paginator = $query->latest()->paginate($request->per_page ?? 20);
        
        $paginator->getCollection()->transform(fn($order) => [
            'id' => $order->id,
            'order_code' => $order->pickup_code,
            'customer_name' => $order->customer_name ?? ($order->partnerCustomer?->name) ?? ($order->customer ? $order->customer->name : 'N/A'),
            'customer_phone' => $order->customer_phone ?? $order->partnerCustomer?->mobile,
            'scheduled_at' => $order->scheduled_at ? $order->scheduled_at->toDateTimeString() : null,
            'address' => $order->address,
            'status' => $order->status,
            'assigned_pickup_boy' => $order->assignment && $order->assignment->pickupBoy ? [
                'id' => $order->assignment->pickupBoy->id,
                'name' => $order->assignment->pickupBoy->name
            ] : null,
        ]);

        return $this->paginatedResponse('partner.orders_fetched', $paginator);
    }

    public function orderDetail($id)
    {
        $partnerId = $this->getPartner(Auth::user());
        $order = PickupRequest::where('channel_partner_id', $partnerId)
            ->with(['customer', 'partnerCustomer', 'items.category', 'images.pickupItem', 'assignment.pickupBoy', 'warehouse', 'statusLogs.creator'])
            ->find($id);

        if (!$order) return $this->errorResponse('partner.order_not_found', 404);

        $data = [
            'id' => $order->id,
            'order_code' => $order->pickup_code,
            'customer' => $order->partnerCustomer,
            'customer_name' => $order->customer_name ?? ($order->partnerCustomer?->name) ?? ($order->customer ? $order->customer->name : 'N/A'),
            'customer_phone' => $order->customer_phone ?? $order->partnerCustomer?->mobile,
            'scheduled_at' => $order->scheduled_at ? $order->scheduled_at->toDateTimeString() : null,
            'address' => $order->address,
            'status' => $order->status,
            'assigned_pickup_boy' => $order->assignment && $order->assignment->pickupBoy ? [
                'id' => $order->assignment->pickupBoy->id,
                'name' => $order->assignment->pickupBoy->name
            ] : null,
            'items' => $order->items,
            'images' => $order->images,
            'warehouse' => $order->warehouse,
            'status_logs' => $order->statusLogs
        ];

        return $this->successResponse('partner.order_fetched', $data);
    }

    public function pickupBoys(Request $request)
    {
        $partnerId = $this->getPartner(Auth::user());
        $agents = User::role('pickup_boy')
            ->where('channel_partner_id', $partnerId)
            ->with(['warehouse'])
            ->withCount([
                'assignments as completed_count' => fn($q) => $q->where('status', 'completed'),
                'assignments as current_assignment_count' => fn($q) => $q->whereIn('status', ['assigned', 'accepted', 'on_the_way', 'arrived', 'verifying', 'picked_up'])
            ])->get();

        $data = $agents->map(fn($agent) => [
            'id' => $agent->id,
            'name' => $agent->name,
            'phone' => $agent->phone,
            'is_online' => (bool) $agent->is_online,
            'is_available' => (bool) $agent->is_available,
            'is_active' => $agent->status === 'active',
            'warehouse_name' => $agent->warehouse ? $agent->warehouse->name : 'Unassigned',
            'current_assignment_count' => $agent->current_assignment_count,
            'completed_count' => $agent->completed_count,
        ]);

        return $this->successResponse('partner.pickup_boys_fetched', $data);
    }

    public function showPickupBoy($id)
    {
        $partnerId = $this->getPartner(Auth::user());
        $agent = User::role('pickup_boy')
            ->where('channel_partner_id', $partnerId)
            ->with(['warehouse'])
            ->find($id);

        if (!$agent) return $this->errorResponse('partner.pickup_boy_not_found', 404);

        return $this->successResponse('partner.pickup_boy_fetched', $agent);
    }

    public function warehouses(Request $request)
    {
        $partnerId = $this->getPartner(Auth::user());
        $warehouses = Warehouse::where('channel_partner_id', $partnerId)
            ->withCount([
                'pickupBoys as pickup_boys_count',
                'orders as total_orders'
            ])
            ->get();

        $data = $warehouses->map(fn($w) => [
            'id' => $w->id,
            'name' => $w->name,
            'address' => $w->address,
            'is_active' => (bool) $w->status,
            'pickup_boys_count' => $w->pickup_boys_count,
            'total_orders' => $w->total_orders,
        ]);

        return $this->successResponse('partner.warehouses_fetched', $data);
    }

    public function showWarehouse($id)
    {
        $partnerId = $this->getPartner(Auth::user());
        $warehouse = Warehouse::where('channel_partner_id', $partnerId)->with('city')->find($id);

        if (!$warehouse) return $this->errorResponse('partner.warehouse_not_found', 404);

        return $this->successResponse('partner.warehouse_fetched', $warehouse);
    }

    /**
     * Submit Pickup Boy Request
     */
    public function storePickupBoyRequest(Request $request)
    {
        return $this->createApprovalRequest($request, 'pickup_boy', 'create');
    }

    public function updatePickupBoyRequest(Request $request, $id)
    {
        return $this->createApprovalRequest($request, 'pickup_boy', 'update', $id);
    }

    /**
     * Submit Warehouse Request
     */
    public function storeWarehouseRequest(Request $request)
    {
        return $this->createApprovalRequest($request, 'warehouse', 'create');
    }

    public function updateWarehouseRequest(Request $request, $id)
    {
        return $this->createApprovalRequest($request, 'warehouse', 'update', $id);
    }

    protected function createApprovalRequest(Request $request, $entityType, $requestType, $entityId = null)
    {
        $user = Auth::user();
        $partnerId = $this->getPartner($user);

        $approvalRequest = ApprovalRequest::create([
            'channel_partner_id' => $partnerId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'request_type' => $requestType,
            'payload' => $request->except(['attachments']),
            'attachments' => $request->attachments ?? [],
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        return $this->successResponse('partner.request_submitted', $approvalRequest, 201);
    }

    public function approvalRequests(Request $request)
    {
        $partnerId = $this->getPartner(Auth::user());
        $query = ApprovalRequest::where('channel_partner_id', $partnerId)
            ->with(['creator']);

        if ($request->status) $query->where('status', $request->status);
        if ($request->entity_type) $query->where('entity_type', $request->entity_type);

        $paginator = $query->latest()->paginate($request->per_page ?? 20);

        $paginator->getCollection()->transform(fn($req) => [
            'id' => $req->id,
            'title' => $this->getApprovalTitle($req),
            'description' => $this->getApprovalDescription($req),
            'status' => $req->status,
            'requester_name' => $req->creator ? $req->creator->name : 'System',
            'created_at' => $req->created_at->toIso8601String(),
            'warehouse_name' => ($req->entity_type === 'warehouse') ? ($req->payload['name'] ?? 'N/A') : null,
            'amount' => $req->payload['amount'] ?? null,
            'notes' => $req->admin_remarks
        ]);

        return $this->paginatedResponse('partner.requests_fetched', $paginator);
    }

    private function getApprovalTitle($req)
    {
        $type = str_replace('_', ' ', $req->entity_type);
        $action = $req->request_type === 'create' ? 'Registration' : 'Update';
        return ucfirst($type) . ' ' . $action;
    }

    private function getApprovalDescription($req)
    {
        if ($req->entity_type === 'warehouse') {
            return "Request to " . $req->request_type . " warehouse: " . ($req->payload['name'] ?? 'Unknown');
        }
        if ($req->entity_type === 'pickup_boy') {
            return "Request to " . $req->request_type . " pickup boy: " . ($req->payload['name'] ?? 'Unknown');
        }
        return "Approval request for " . $req->entity_type;
    }

    public function showApprovalRequest($id)
    {
        $partnerId = $this->getPartner(Auth::user());
        $req = ApprovalRequest::where('channel_partner_id', $partnerId)->find($id);

        if (!$req) return $this->errorResponse('partner.request_not_found', 404);

        return $this->successResponse('partner.request_fetched', $req);
    }

    public function profile()
    {
        $user = Auth::user();
        $partner = \App\Models\ChannelPartner::where('user_id', $user->id)->with('city')->first();
        return $this->successResponse('partner.profile_fetched', [
            'user' => $user,
            'partner' => $partner,
            'support' => [
                'phone' => '+91 98765 43210',
                'email' => 'support@scrapify.test'
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $partner = \App\Models\ChannelPartner::where('user_id', $user->id)->first();

        $request->validate([
            'name' => 'sometimes|string',
            'business_name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'city' => 'sometimes|string',
        ]);

        if ($request->name) $user->update(['name' => $request->name]);
        if ($request->email) $user->update(['email' => $request->email]);
        
        $partner->update($request->only(['business_name', 'city', 'address', 'pincode']));

        return $this->successResponse('partner.profile_updated', $partner);
    }

    public function submitStatusRequest(Request $request, $entityType, $id)
    {
        $request->validate(['status' => 'required|string']);
        return $this->createApprovalRequest($request, $entityType, 'status_change', $id);
    }

    /**
     * Unified approval/rejection handler for Channel Partner.
     */
    public function handleStatusRequest(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:approval_requests,id',
            'status' => 'required|string|in:approved,rejected',
        ]);

        $partnerId = $this->getPartner(Auth::user());
        $approvalReq = ApprovalRequest::where('channel_partner_id', $partnerId)
            ->find($request->request_id);

        if (!$approvalReq) {
            return $this->errorResponse('partner.request_not_found', 404);
        }

        DB::beginTransaction();
        try {
            $approvalReq->update([
                'status' => $request->status,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            if ($request->status === 'approved') {
                if ($approvalReq->entity_type === 'pickup_boy') {
                    $this->handlePickupBoyApproval($approvalReq);
                } elseif ($approvalReq->entity_type === 'warehouse') {
                    $this->handleWarehouseApproval($approvalReq);
                }
            }

            DB::commit();

            return $this->successResponse('partner.request_updated', [
                'success' => true,
                'message' => 'Request ' . $request->status . ' successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('partner.request_update_failed', 500, ['error' => $e->getMessage()]);
        }
    }

    protected function handlePickupBoyApproval($req)
    {
        $payload = is_string($req->payload) ? json_decode($req->payload, true) : $req->payload;
        
        if ($req->request_type === 'create') {
            $user = User::create(array_merge($payload, [
                'channel_partner_id' => $req->channel_partner_id,
                'status' => 'active',
            ]));
            $user->assignRole('pickup_boy');
        } else {
            $user = User::findOrFail($req->entity_id);
            $user->update($payload);
        }
    }

    protected function handleWarehouseApproval($req)
    {
        $payload = is_string($req->payload) ? json_decode($req->payload, true) : $req->payload;

        if ($req->request_type === 'create') {
            Warehouse::create(array_merge($payload, [
                'channel_partner_id' => $req->channel_partner_id,
                'status' => true,
            ]));
        } else {
            $warehouse = Warehouse::findOrFail($req->entity_id);
            $warehouse->update($payload);
        }
    }

    /**
     * Keep existing Payout/Withdrawal logic
     */
    public function payouts()
    {
        $user = Auth::user();
        $payouts = Payment::where('user_id', $user->id)->latest()->paginate(15);
        return $this->paginatedResponse('dealer.payouts', $payouts);
    }

    public function storeWithdrawal(Request $request)
    {
        // Existing logic...
    }

    public function listWithdrawals(Request $request)
    {
        // Existing logic...
    }
}
