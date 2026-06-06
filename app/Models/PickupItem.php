<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_request_id',
        'category_id',
        'product_name',
        'quantity',
        'weight',
        'condition',
        'attributes', // JSON
        'price_per_unit',
        'total_price',
        'image_path',
        'remarks',
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }
}
