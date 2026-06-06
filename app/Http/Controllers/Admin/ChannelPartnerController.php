<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartner;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ChannelPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = ChannelPartner::with('user');

        if ($request->status) {
            $query->where('registration_status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('business_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        return Inertia::render('Admin/ChannelPartners/Index', [
            'partners' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show($id)
    {
        $partner = ChannelPartner::with(['user', 'approver'])->findOrFail($id);
        return Inertia::render('Admin/ChannelPartners/Show', [
            'partner' => $partner
        ]);
    }

    public function approve(Request $request, $id)
    {
        $partner = ChannelPartner::findOrFail($id);

        DB::transaction(function () use ($partner, $request) {
            // 1. Create User account if not exists
            if (!$partner->user_id) {
                $user = User::create([
                    'name' => $partner->full_name,
                    'email' => $partner->email,
                    'phone' => $partner->phone,
                    'password' => Hash::make(Str::random(12)), // Standard practice: trigger password reset
                    'status' => 'active',
                    'channel_partner_id' => $partner->id,
                ]);

                $user->assignRole('channel_partner');
                $partner->user_id = $user->id;
            }

            // 2. Update Status
            $partner->update([
                'registration_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'onboarding_fee_amount' => $request->fee_amount ?? 0,
                'login_enabled' => ($partner->fee_payment_status === 'paid' || $partner->fee_payment_status === 'waived'),
            ]);
            
            // Note: Mail should be triggered here for password setup
        });

        return back()->with('success', 'Channel Partner approved successfully.');
    }

    public function updateFeeStatus(Request $request, $id)
    {
        $partner = ChannelPartner::findOrFail($id);
        
        $partner->update([
            'fee_payment_status' => $request->status,
            'fee_paid_at' => $request->status === 'paid' ? now() : null,
            'payment_reference' => $request->reference,
            'payment_remark' => $request->remark,
            'login_enabled' => in_array($request->status, ['paid', 'waived']) && $partner->registration_status === 'approved',
        ]);

        return back()->with('success', 'Fee status updated successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'admin_remark' => 'nullable|string|max:1000',
        ]);

        $partner = ChannelPartner::findOrFail($id);

        $partner->update([
            'registration_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'admin_remark' => $request->admin_remark,
            'rejected_at' => now(),
            'login_enabled' => false,
        ]);

        return back()->with('success', 'Channel Partner application rejected.');
    }
}
