<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralCoupon;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class ReferralAdminController extends Controller
{
    use ApiResponseTrait;

    public function referrals(Request $request)
    {
        $query = Referral::with(['referrer:id,name,phone,referral_code', 'referred:id,name,phone', 'coupon']);

        if ($request->status)        $query->where('status', $request->status);
        if ($request->reward_status) $query->where('reward_status', $request->reward_status);
        if ($request->search) {
            $query->where('referral_code', 'like', '%' . $request->search . '%');
        }

        return $this->successResponse('referral.list', $query->latest()->paginate($request->input('per_page', 20)));
    }

    public function coupons(Request $request)
    {
        $query = ReferralCoupon::with(['user:id,name,phone', 'booking:id,pickup_code']);

        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $query->where('coupon_code', 'like', '%' . $request->search . '%');
        }

        return $this->successResponse('referral.coupons_list', $query->latest()->paginate($request->input('per_page', 20)));
    }

    public function cancelCoupon($id)
    {
        $coupon = ReferralCoupon::find($id);
        if (!$coupon) return $this->errorResponse('coupon.not_found', 404);

        if ($coupon->status === 'used') {
            return $this->errorResponse('coupon.already_used', 422);
        }

        $coupon->update(['status' => 'cancelled']);
        return $this->successResponse('coupon.cancelled', $coupon);
    }
}
