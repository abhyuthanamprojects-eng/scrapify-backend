<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupAssignmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_request_id',
        'old_pickup_boy_id',
        'new_pickup_boy_id',
        'assigned_by_user_id',
        'reason',
    ];

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function oldPickupBoy()
    {
        return $this->belongsTo(User::class, 'old_pickup_boy_id');
    }

    public function newPickupBoy()
    {
        return $this->belongsTo(User::class, 'new_pickup_boy_id');
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
