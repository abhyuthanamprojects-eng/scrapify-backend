<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCoupon extends Model
{
    protected $fillable = [
        'user_id',
        'referral_id',
        'coupon_code',
        'coupon_type',
        'coupon_value',
        'min_booking_value',
        'max_discount_value',
        'expiry_date',
        'status',
        'used_booking_id',
        'used_at',
        'created_by',
        'created_by_role',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'used_at'     => 'datetime',
        'coupon_value'       => 'decimal:2',
        'min_booking_value'  => 'decimal:2',
        'max_discount_value' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }

    public function booking()
    {
        return $this->belongsTo(PickupRequest::class, 'used_booking_id');
    }

    public function isUsable(): bool
    {
        return $this->status === 'active' && $this->expiry_date->isFuture();
    }
}
