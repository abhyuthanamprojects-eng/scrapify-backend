<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PickupRequest extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\BelongsToPartner;

    protected $appends = ['shipping_charge', 'subtotal_amount', 'payment_receipt_image_url'];

    public function getShippingChargeAttribute()
    {
        return $this->metadata['pricing_breakdown']['shipping_charge'] ?? 0;
    }

    public function getSubtotalAmountAttribute()
    {
        return $this->metadata['pricing_breakdown']['subtotal_amount'] ?? $this->estimated_amount;
    }

    public function getPaymentReceiptImageUrlAttribute()
    {
        $path = $this->payment_receipt_image;

        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

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
        'status_new',
        'customer_email',
        'meeting_type',
        'warehouse_assigned_at',
        'pickup_started_at',
        'pickup_completed_at',
        'warehouse_received_at',
        'payment_pending_at',
        'payment_completed_at',
        'completed_at',
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
        'payment_receipt_image',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'price_locked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function setScheduledAtAttribute($value)
    {
        if ($value) {
            $this->attributes['scheduled_at'] = \Carbon\Carbon::parse($value)
                ->setTimezone(config('app.timezone'));
        } else {
            $this->attributes['scheduled_at'] = null;
        }
    }

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

    public function requestAttributes()
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

    /**
     * Get latest corporate booking estimate
     */
    public function latestEstimate()
    {
        return $this->hasOne(CorporateBookingEstimate::class, 'request_id')
            ->latest('created_at');
    }

    /**
     * Get current pickup assignment
     */
    public function currentAssignment()
    {
        return $this->hasOne(Assignment::class)
            ->whereIn('status', ['assigned', 'accepted'])
            ->latest('created_at');
    }

    /**
     * Transition to a new status with validation and logging
     */
    public function transitionTo(
        \App\Enums\RequestStatus $newStatus,
        $userId,
        $userRole = 'system',
        $reason = null
    ) {
        return \App\Services\RequestStatusTransitionService::transition(
            $this,
            $newStatus,
            $userId,
            $userRole,
            $reason
        );
    }
}
