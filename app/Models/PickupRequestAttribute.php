<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupRequestAttribute extends Model
{
    protected $fillable = ['pickup_request_id', 'attribute_id', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
