<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PickupRequest extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\BelongsToPartner;
    protected $fillable = [
        'request_type',
        'donation_category',
        'customer_id',
        'partner_customer_id',
        'address_id',
        'warehouse_id',
        'pickup_code',
        'customer_name',
        'customer_phone',
        'created_by',
        'city_id',
        'address',
        'latitude',
        'longitude',
        'scheduled_at',
        'payout_method',
        'reschedule_reason',
        'status',
        'estimated_amount',
        'final_amount',
        'cancellation_reason',
        'rating',
        'review',
        'payment_detail_id',
        'metadata',
        'channel_partner_id',
        'referral_coupon_id',
        'coupon_code',
        'coupon_discount_value',
        'price_locked_at',
        'final_amount_modified_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'price_locked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function paymentDetail()
    {
        return $this->belongsTo(PaymentDetail::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function attributes()
    {
        return $this->hasMany(PickupRequestAttribute::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function referralCoupon()
    {
        return $this->belongsTo(ReferralCoupon::class, 'referral_coupon_id');
    }

    public function priceLogs()
    {
        return $this->hasMany(PickupPriceLog::class);
    }

    public function isPriceLocked(): bool
    {
        return !is_null($this->price_locked_at);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function partnerCustomer()
    {
        return $this->belongsTo(ChannelPartnerCustomer::class, 'partner_customer_id');
    }

    public function items()
    {
        return $this->hasMany(PickupItem::class);
    }

    public function images()
    {
        return $this->hasMany(PickupImage::class);
    }

    public function assignment()
    {
        return $this->hasOne(Assignment::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function channelPartner()
    {
        return $this->belongsTo(ChannelPartner::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(PickupStatusLog::class);
    }

    public function assignmentHistories()
    {
        return $this->hasMany(PickupAssignmentHistory::class);
    }
}
