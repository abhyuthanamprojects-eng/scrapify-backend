<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupBoyLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_boy_id',
        'latitude',
        'longitude',
    ];

    public function pickupBoy()
    {
        return $this->belongsTo(User::class, 'pickup_boy_id');
    }
}
