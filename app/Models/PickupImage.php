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
        if (!$this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        if (file_exists(public_path($this->image_path))) {
            return asset($this->image_path);
        }

        return asset('storage/' . ltrim($this->image_path, '/'));
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
