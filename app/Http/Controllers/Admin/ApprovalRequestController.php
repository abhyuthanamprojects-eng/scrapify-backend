<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApprovalRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['channelPartner:id,full_name,business_name', 'creator:id,name']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->entity_type) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('channelPartner', fn($sub) => $sub->where('full_name', 'like', "%$s%")->orWhere('business_name', 'like', "%$s%"))
                  ->orWhereHas('creator', fn($sub) => $sub->where('name', 'like', "%$s%"));
            });
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/ApprovalRequests/Index', [
            'requests' => $requests,
            'filters' => $request->only(['status', 'entity_type', 'search']),
        ]);
    }

    public function show($id)
    {
        $req = ApprovalRequest::with(['channelPartner', 'creator:id,name,email,phone', 'approver:id,name'])->findOrFail($id);

        return Inertia::render('Admin/ApprovalRequests/Show', [
            'approvalRequest' => $req,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $req = ApprovalRequest::findOrFail($id);

        if ($req->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        try {
            DB::beginTransaction();

            if ($req->entity_type === 'pickup_boy' && $req->request_type === 'create') {
                $payload = $req->payload;
                $user = User::create([
                    'name' => $payload['name'] ?? 'Pickup Boy',
                    'email' => $payload['email'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                    'password' => bcrypt($payload['phone'] ?? 'password'),
                    'status' => 'active',
                    'channel_partner_id' => $req->channel_partner_id,
                ]);
                $user->assignRole('pickup_boy');
                $req->entity_id = $user->id;
            } elseif ($req->entity_type === 'warehouse' && $req->request_type === 'create') {
                $payload = $req->payload;
                $warehouse = Warehouse::create([
                    'name' => $payload['name'] ?? 'Warehouse',
                    'address' => $payload['address'] ?? '',
                    'city_id' => $payload['city_id'] ?? null,
                    'pincode' => $payload['pincode'] ?? '',
                    'capacity_kg' => $payload['capacity_kg'] ?? null,
                    'channel_partner_id' => $req->channel_partner_id,
                    'status' => true,
                ]);
                $req->entity_id = $warehouse->id;
            } elseif ($req->entity_type === 'channel_partner_registration') {
                $partner = \App\Models\ChannelPartner::findOrFail($req->entity_id);
                $partner->update([
                    'registration_status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'login_enabled' => true,
                ]);

                $user = User::where('email', $partner->email)->orWhere('phone', $partner->phone)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $partner->full_name,
                        'email' => $partner->email,
                        'phone' => $partner->phone,
                        'password' => bcrypt('password'),
                        'status' => 'active',
                        'channel_partner_id' => $partner->id,
                    ]);
                }
                if (!$user->hasRole('channel_partner')) {
                    $user->assignRole('channel_partner');
                }
                $partner->update(['user_id' => $user->id]);
            }

            $req->update([
                'status' => 'approved',
                'admin_remarks' => $request->remarks,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            return back()->with('success', 'Request approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);

        $req = ApprovalRequest::findOrFail($id);

        if ($req->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        $req->update([
            'status' => 'rejected',
            'admin_remarks' => $request->remarks,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Also reject partner registration if applicable
        if ($req->entity_type === 'channel_partner_registration') {
            $partner = \App\Models\ChannelPartner::find($req->entity_id);
            if ($partner) {
                $partner->update([
                    'registration_status' => 'rejected',
                    'rejection_reason' => $request->remarks,
                    'rejected_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Request rejected.');
    }
}
