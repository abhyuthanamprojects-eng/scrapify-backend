<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'email',
        'aadhaar_number',
        'pan_number',
        'gst_number',
        'business_name',
        'address',
        'city',
        'state',
        'pincode',
        'opening_location_name',
        'latitude',
        'longitude',
        'registration_status',
        'admin_remark',
        'rejection_reason',
        'onboarding_fee_required',
        'onboarding_fee_amount',
        'fee_payment_status',
        'fee_paid_at',
        'payment_reference',
        'payment_remark',
        'approved_by',
        'approved_at',
        'rejected_at',
        'login_enabled',
        'warehouse_limit',
    ];

    protected $casts = [
        'onboarding_fee_required' => 'boolean',
        'onboarding_fee_amount' => 'decimal:2',
        'fee_paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'login_enabled' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function managedPickupBoys()
    {
        return $this->hasMany(User::class, 'channel_partner_id');
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'channel_partner_id');
    }

    public function pickupRequests()
    {
        return $this->hasMany(PickupRequest::class, 'channel_partner_id');
    }

    public function approvalRequests()
    {
        return $this->hasMany(ApprovalRequest::class, 'channel_partner_id');
    }
}
