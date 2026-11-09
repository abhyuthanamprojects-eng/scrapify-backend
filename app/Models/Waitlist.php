<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    protected $table = 'waitlist';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'city',
        'state',
        'location_name',
        'pincode',
        'latitude',
        'longitude',
        'message',
        'status',
    ];

    /**
     * Keep one representative row for repeated submissions of the same request.
     * The newest row is retained so the latest status and location data remain visible.
     */
    public function scopeWithoutDuplicates(Builder $query): Builder
    {
        $table = $this->getTable();
        $representativeIds = static::query()
            ->selectRaw('MAX(id) as id')
            ->groupByRaw("LOWER(TRIM(name)), TRIM(phone), LOWER(TRIM(COALESCE(email, ''))), LOWER(TRIM(city)), LOWER(TRIM(COALESCE(state, '')))");

        return $query->whereIn("{$table}.id", $representativeIds);
    }
}
