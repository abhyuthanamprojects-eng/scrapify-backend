<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'name' => 'array',
        'status' => 'boolean',
        'requires_details' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image_path ? url($this->image_path) : null;
    }

    /**
     * Get translated name based on current app locale.
     */
    public function getTranslatedName()
    {
        $name = $this->name;
        if (!is_array($name)) {
            return $name;
        }

        $locale = app()->getLocale();
        return $name[$locale] ?? ($name['en'] ?? (array_values($name)[0] ?? 'Item'));
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'category_attributes')
            ->withPivot('is_required')
            ->withTimestamps();
    }

    public function pricingRules()
    {
        return $this->hasMany(PricingRule::class);
    }

    public function variantPricingRules()
    {
        return $this->hasMany(PricingVariantRule::class);
    }

    public function categoryType()
    {
        return $this->belongsTo(CategoryType::class);
    }
}
