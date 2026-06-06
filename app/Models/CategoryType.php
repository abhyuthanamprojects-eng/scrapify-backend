<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CategoryType extends Model
{
    protected $fillable = ['name', 'slug', 'status', 'image_path', 'show_in_corporate_booking'];

    protected $casts = [
        'name' => 'array',
        'status' => 'boolean',
        'show_in_corporate_booking' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset($this->image_path) : null;
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

    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}
