<?php

namespace App\Models;

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
}
