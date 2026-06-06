<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupPriceLog extends Model
{
    protected $fillable = [
        'pickup_request_id',
        'old_amount',
        'new_amount',
        'modified_by',
        'modified_by_type',
        'reason',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
