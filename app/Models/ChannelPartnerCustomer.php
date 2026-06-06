<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelPartnerCustomer extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\BelongsToPartner;

    protected $fillable = [
        'channel_partner_id',
        'name',
        'mobile',
        'address',
        'city',
        'pincode',
        'landmark',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function channelPartner()
    {
        return $this->belongsTo(ChannelPartner::class);
    }

    public function pickupRequests()
    {
        return $this->hasMany(PickupRequest::class, 'partner_customer_id');
    }
}
