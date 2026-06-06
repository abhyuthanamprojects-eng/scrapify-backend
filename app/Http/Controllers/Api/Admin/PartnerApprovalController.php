<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class PartnerApprovalController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/admin/partner-approvals",
        operationId: "listApprovalRequests",
        tags: ["Admin Partner Approvals"],
        summary: "List all partner approval requests",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Requests fetched")
        ]
    )]
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['channelPartner', 'creator']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->entity_type) {
            $types = explode('|', $request->entity_type);
            $query->whereIn('entity_type', $types);
        }
        
        if ($request->channel_partner_id) {
            $query->where('channel_partner_id', $request->channel_partner_id);
        }

        return $this->paginatedResponse('admin.partner_requests_fetched', $query->latest()->paginate($request->per_page ?? 20));
    }

    public function listPartners(Request $request)
    {
        $query = \App\Models\ChannelPartner::with(['user', 'city']);
        
        if ($request->status) {
            $query->where('registration_status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('full_name', 'like', "%$s%")->orWhere('business_name', 'like', "%$s%"));
        }
        return $this->paginatedResponse('admin.partners_fetched', $query->latest()->paginate(20));
    }

    public function getPartnerDetail($id)
    {
        $partner = \App\Models\ChannelPartner::with(['user', 'city'])->findOrFail($id);
        return $this->successResponse('admin.partner_fetched', $partner);
    }

    public function updateWarehouseLimit(Request $request, $id)
    {
        $request->validate([
            'warehouse_limit' => 'required|integer|min:0',
        ]);

        $partner = \App\Models\ChannelPartner::findOrFail($id);
        $partner->update(['warehouse_limit' => $request->warehouse_limit]);

        return $this->successResponse('admin.partner_limit_updated', [
            'id' => $partner->id,
            'warehouse_limit' => $partner->warehouse_limit
        ]);
    }

    public function getPartnerOversight($id, $type)
    {
        $partner = \App\Models\ChannelPartner::findOrFail($id);
        
        if ($type === 'orders') {
            $data = \App\Models\PickupRequest::where('channel_partner_id', $id)->with(['customer', 'warehouse'])->latest()->paginate(20);
        } elseif ($type === 'pickup-boys') {
            $data = \App\Models\User::role('pickup_boy')->where('channel_partner_id', $id)->with(['warehouse'])->get();
        } elseif ($type === 'warehouses') {
            $data = \App\Models\Warehouse::where('channel_partner_id', $id)->with('city')->get();
        } else {
            return $this->errorResponse('admin.invalid_oversight_type', 400);
        }

        return $this->successResponse('admin.partner_oversight_fetched', $data);
    }

    public function show($id)
    {
        $req = ApprovalRequest::with(['channelPartner', 'creator'])->find($id);
        if (!$req) return $this->errorResponse('admin.request_not_found', 404);
        return $this->successResponse('admin.partner_request_fetched', $req);
    }

    public function approve(Request $request, $id)
    {
        $req = ApprovalRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return $this->errorResponse('admin.request_already_processed', 400);
        }

        try {
            DB::beginTransaction();

            if ($req->entity_type === 'pickup_boy') {
                $this->handlePickupBoyApproval($req);
            } elseif ($req->entity_type === 'warehouse') {
                $this->handleWarehouseApproval($req);
            } elseif ($req->entity_type === 'channel_partner_registration') {
                $this->handleChannelPartnerApproval($req);
            }

            $req->update([
                'status' => 'approved',
                'admin_remarks' => $request->remarks,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            return $this->successResponse('admin.request_approved');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    protected function handleChannelPartnerApproval($req)
    {
        $partner = \App\Models\ChannelPartner::findOrFail($req->entity_id);
        
        // Update partner status
        $partner->update([
            'registration_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'login_enabled' => true,
        ]);

        // Create or Update User
        $user = \App\Models\User::where('email', $partner->email)
            ->orWhere('phone', $partner->phone)
            ->first();

        if (!$user) {
            $user = \App\Models\User::create([
                'name' => $partner->full_name,
                'email' => $partner->email,
                'phone' => $partner->phone,
                'password' => bcrypt('password'), // Should be changed on first login or sent via OTP
                'status' => 'active',
                'channel_partner_id' => $partner->id,
            ]);
        } else {
            $user->update([
                'channel_partner_id' => $partner->id,
                'status' => 'active',
            ]);
        }

        if (!$user->hasRole('channel_partner')) {
            $user->assignRole('channel_partner');
        }

        $partner->update(['user_id' => $user->id]);
    }

    protected function handlePickupBoyApproval($req)
    {
        $payload = $req->payload;
        
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
        $payload = $req->payload;

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

    public function reject(Request $request, $id)
    {
        $req = ApprovalRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return $this->errorResponse('admin.request_already_processed', 400);
        }

        $req->update([
            'status' => 'rejected',
            'admin_remarks' => $request->remarks,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        // If it's a partner registration, also update the partner record
        if ($req->entity_type === 'channel_partner_registration') {
            $partner = \App\Models\ChannelPartner::find($req->entity_id);
            if ($partner) {
                $partner->update([
                    'registration_status' => 'rejected',
                    'rejection_reason' => $request->remarks,
                    'rejected_at' => now()
                ]);
            }
        }

        return $this->successResponse('admin.request_rejected');
    }
}
