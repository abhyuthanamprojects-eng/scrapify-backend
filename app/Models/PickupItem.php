<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupItem extends Model
{
    use HasFactory;

    protected $appends = ['carbon_per_unit', 'total_carbon_saved'];

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

    public function getCarbonPerUnitAttribute(): float
    {
        $category = $this->relationLoaded('category')
            ? $this->category
            : $this->category()->with('pricingRules')->first();

        if (!$category) {
            return 0.0;
        }

        if (!$category->relationLoaded('pricingRules')) {
            $category->load('pricingRules');
        }

        $baseRule = $category->pricingRules
            ->firstWhere('attribute_option_id', null)
            ?? $category->pricingRules->first();

        return $baseRule && $baseRule->carbon_per_unit !== null
            ? round((float) $baseRule->carbon_per_unit, 3)
            : 0.0;
    }

    public function getTotalCarbonSavedAttribute(): float
    {
        $units = $this->weight && (float) $this->weight > 0
            ? (float) $this->weight
            : (float) ($this->quantity ?? 0);

        return round($units * $this->carbon_per_unit, 3);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }
}
