<?php

namespace App\Services;

use App\Models\PickupRequest;
use App\Models\Referral;
use App\Models\ReferralCoupon;
use App\Models\ReferralSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public function generateUniqueReferralCode(User $user): string
    {
        $base = strtoupper(preg_replace('/[^A-Z]/i', '', $user->name ?? '')) ?: 'USER';
        $base = substr($base, 0, 4);
        if (strlen($base) < 3) {
            $base = str_pad($base, 3, 'X');
        }

        for ($i = 0; $i < 10; $i++) {
            $suffix = (string) random_int(10, 99);
            $code = substr($base, 0, 6 - strlen($suffix)) . $suffix;
            $code = strtoupper(substr($code, 0, 6));
            if (!User::where('referral_code', $code)->exists()) {
                return $code;
            }
        }

        // Fallback: random 6-char alphanumeric
        do {
            $code = strtoupper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    public function ensureReferralCode(User $user): string
    {
        if ($user->referral_code) return $user->referral_code;
        $code = $this->generateUniqueReferralCode($user);
        $user->forceFill(['referral_code' => $code])->save();
        return $code;
    }

    /**
     * @return array{ok:bool, message:string, referrer?:User}
     */
    public function validateReferralCode(string $code, ?User $currentUser = null): array
    {
        $code = strtoupper(trim($code));

        $setting = ReferralSetting::active();
        if (!$setting || !$setting->isLive()) {
            return ['ok' => false, 'message' => 'referral.campaign_inactive'];
        }

        $referrer = User::where('referral_code', $code)->first();
        if (!$referrer) {
            return ['ok' => false, 'message' => 'referral.invalid_code'];
        }

        if (!$referrer->hasRole('customer')) {
            return ['ok' => false, 'message' => 'referral.invalid_code'];
        }

        if ($currentUser && $currentUser->id === $referrer->id) {
            return ['ok' => false, 'message' => 'referral.self_not_allowed'];
        }

        if ($currentUser && Referral::where('referred_user_id', $currentUser->id)->exists()) {
            return ['ok' => false, 'message' => 'referral.already_used'];
        }

        $cap = $setting->max_referrals_per_user;
        if ($cap) {
            $count = Referral::where('referrer_user_id', $referrer->id)
                ->whereIn('status', ['pending', 'successful'])
                ->count();
            if ($count >= $cap) {
                return ['ok' => false, 'message' => 'referral.cap_reached'];
            }
        }

        return ['ok' => true, 'message' => 'referral.valid', 'referrer' => $referrer];
    }

    /**
     * Apply referral relationship + issue coupon to referrer.
     * Should be called inside a transaction by caller, but we wrap defensively.
     */
    public function applyReferral(string $code, User $newUser): ?Referral
    {
        if (!$newUser->hasRole('customer')) return null;

        $check = $this->validateReferralCode($code, $newUser);
        if (!$check['ok']) return null;

        return DB::transaction(function () use ($code, $newUser, $check) {
            $referral = Referral::create([
                'referrer_user_id' => $check['referrer']->id,
                'referred_user_id' => $newUser->id,
                'referral_code'    => strtoupper($code),
                'status'           => 'successful',
                'reward_status'    => 'pending',
            ]);

            $coupon = $this->issueReferralCoupon($referral);
            if ($coupon) {
                $referral->update([
                    'reward_coupon_id' => $coupon->id,
                    'reward_status'    => 'issued',
                ]);
            }

            return $referral->fresh('coupon');
        });
    }

    public function issueReferralCoupon(Referral $referral): ?ReferralCoupon
    {
        $setting = ReferralSetting::active();
        if (!$setting) return null;

        do {
            $code = 'REF' . strtoupper(Str::random(7));
        } while (ReferralCoupon::where('coupon_code', $code)->exists());

        return ReferralCoupon::create([
            'user_id'            => $referral->referrer_user_id,
            'referral_id'        => $referral->id,
            'coupon_code'        => $code,
            'coupon_type'        => $setting->reward_type,
            'coupon_value'       => $setting->reward_value,
            'min_booking_value'  => $setting->min_booking_value,
            'max_discount_value' => $setting->max_reward_value,
            'expiry_date'        => Carbon::today()->addDays($setting->coupon_expiry_days),
            'status'             => 'active',
            'created_by'         => $setting->managed_by_user_id,
            'created_by_role'    => $setting->managed_by_role,
        ]);
    }

    /**
     * @return array{ok:bool, message:string, coupon?:ReferralCoupon, discount?:float}
     */
    public function validateCoupon(string $code, User $user, float $bookingAmount): array
    {
        $code = strtoupper(trim($code));

        $coupon = ReferralCoupon::where('coupon_code', $code)->first();
        if (!$coupon) {
            return ['ok' => false, 'message' => 'coupon.invalid'];
        }

        if ($coupon->user_id !== $user->id) {
            return ['ok' => false, 'message' => 'coupon.not_owned'];
        }

        if ($coupon->status === 'used') {
            return ['ok' => false, 'message' => 'coupon.already_used'];
        }

        if ($coupon->status !== 'active') {
            return ['ok' => false, 'message' => 'coupon.inactive'];
        }

        if (Carbon::parse($coupon->expiry_date)->isPast()) {
            $coupon->update(['status' => 'expired']);
            return ['ok' => false, 'message' => 'coupon.expired'];
        }

        if ($coupon->min_booking_value && $bookingAmount < (float) $coupon->min_booking_value) {
            return ['ok' => false, 'message' => 'coupon.min_value_not_met'];
        }

        $discount = $this->calculateDiscount($coupon, $bookingAmount);

        return ['ok' => true, 'message' => 'coupon.valid', 'coupon' => $coupon, 'discount' => $discount];
    }

    public function calculateDiscount(ReferralCoupon $coupon, float $bookingAmount): float
    {
        $value = (float) $coupon->coupon_value;
        $discount = match ($coupon->coupon_type) {
            'percentage' => round($bookingAmount * ($value / 100), 2),
            default      => $value, // fixed + extra_value = flat add
        };

        if ($coupon->max_discount_value) {
            $discount = min($discount, (float) $coupon->max_discount_value);
        }
        return round($discount, 2);
    }

    public function applyCouponToBooking(ReferralCoupon $coupon, PickupRequest $booking, float $discount): void
    {
        DB::transaction(function () use ($coupon, $booking, $discount) {
            $booking->forceFill([
                'referral_coupon_id'    => $coupon->id,
                'coupon_code'           => $coupon->coupon_code,
                'coupon_discount_value' => $discount,
                'estimated_amount'      => (float) $booking->estimated_amount + $discount,
            ])->save();

            $coupon->update([
                'status'          => 'used',
                'used_booking_id' => $booking->id,
                'used_at'         => now(),
            ]);

            if ($coupon->referral_id) {
                Referral::where('id', $coupon->referral_id)->update([
                    'reward_status' => 'used',
                    'used_at'       => now(),
                ]);
            }
        });
    }
}
