<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_request_id',
        'pickup_item_id',
        'image_path',
        'type', // 'pickup' (user uploaded) or 'verification' (pickup boy)
        'latitude',
        'longitude',
        'remarks',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return $this->image_path ? asset($this->image_path) : null;
    }

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function pickupItem()
    {
        return $this->belongsTo(PickupItem::class);
    }
}
