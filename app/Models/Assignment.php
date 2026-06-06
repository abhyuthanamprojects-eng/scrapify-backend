<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_request_id',
        'pickup_boy_id',
        'warehouse_id',
        'status', // assigned, accepted, reached_location, pickup_started, pickup_completed, cancelled, reassigned, rejected, completed
        'assigned_by',
        'assigned_by_type',
        'remarks',
        'notes',
        'assigned_at',
        'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function pickupBoy()
    {
        return $this->belongsTo(User::class, 'pickup_boy_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
