<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingVariantRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'option_values' => 'array',
        'base_price' => 'decimal:2',
        'status' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
