<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralCoupon;
use App\Models\ReferralSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->tab ?? 'settings';

        // Settings
        $settings = ReferralSetting::latest()->get();

        // Referrals
        $referralQuery = Referral::with(['referrer:id,name,phone', 'referred:id,name,phone']);
        if ($request->referral_status) {
            $referralQuery->where('status', $request->referral_status);
        }
        $referrals = $referralQuery->latest()->paginate(15)->withQueryString();

        // Coupons
        $couponQuery = ReferralCoupon::with(['user:id,name,phone']);
        if ($request->coupon_status) {
            $couponQuery->where('status', $request->coupon_status);
        }
        $coupons = $couponQuery->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Referrals/Index', [
            'settings' => $settings,
            'referrals' => $referrals,
            'coupons' => $coupons,
            'filters' => $request->only(['tab', 'referral_status', 'coupon_status']),
        ]);
    }

    public function storeSetting(Request $request)
    {
        $request->validate([
            'campaign_name' => 'required|string|max:255',
            'reward_type' => 'required|in:fixed,percentage,extra_value',
            'reward_value' => 'required|numeric|min:0',
            'coupon_expiry_days' => 'required|integer|min:1',
            'min_booking_value' => 'nullable|numeric|min:0',
            'max_reward_value' => 'nullable|numeric|min:0',
            'max_referrals_per_user' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_active')) {
            ReferralSetting::where('is_active', true)->update(['is_active' => false]);
        }

        ReferralSetting::create(array_merge($request->all(), [
            'managed_by_user_id' => auth()->id(),
            'managed_by_role' => 'admin',
        ]));

        return back()->with('success', 'Referral campaign created successfully.');
    }

    public function updateSetting(Request $request, $id)
    {
        $setting = ReferralSetting::findOrFail($id);

        if ($request->keys() === ['is_active'] || ($request->has('is_active') && count($request->except('_token')) === 1)) {
            if ($request->boolean('is_active')) {
                ReferralSetting::where('is_active', true)
                    ->where('id', '!=', $setting->id)
                    ->update(['is_active' => false]);
            }

            $setting->update([
                'is_active' => $request->boolean('is_active'),
            ]);

            return back()->with('success', 'Referral campaign updated.');
        }

        $request->validate([
            'campaign_name' => 'required|string|max:255',
            'reward_type' => 'required|in:fixed,percentage,extra_value',
            'reward_value' => 'required|numeric|min:0',
            'coupon_expiry_days' => 'required|integer|min:1',
            'min_booking_value' => 'nullable|numeric|min:0',
            'max_reward_value' => 'nullable|numeric|min:0',
            'max_referrals_per_user' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        if ($request->boolean('is_active')) {
            ReferralSetting::where('is_active', true)
                ->where('id', '!=', $setting->id)
                ->update(['is_active' => false]);
        }

        $setting->update($request->all());

        return back()->with('success', 'Referral campaign updated.');
    }

    public function cancelCoupon($id)
    {
        $coupon = ReferralCoupon::findOrFail($id);
        $coupon->update(['status' => 'cancelled']);

        return back()->with('success', 'Coupon cancelled.');
    }
}
