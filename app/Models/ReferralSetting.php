<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    protected $fillable = [
        'managed_by_user_id',
        'managed_by_role',
        'campaign_name',
        'is_active',
        'reward_type',
        'reward_value',
        'coupon_expiry_days',
        'min_booking_value',
        'max_reward_value',
        'max_referrals_per_user',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'start_date' => 'date',
        'end_date'   => 'date',
        'reward_value'      => 'decimal:2',
        'min_booking_value' => 'decimal:2',
        'max_reward_value'  => 'decimal:2',
    ];

    public function isLive(): bool
    {
        if (!$this->is_active) return false;
        $today = Carbon::today();
        if ($this->start_date && $today->lt($this->start_date)) return false;
        if ($this->end_date && $today->gt($this->end_date)) return false;
        return true;
    }

    public static function active(): ?self
    {
        return self::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->latest('id')
            ->first();
    }
}
