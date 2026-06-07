<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RequestStatusLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'changed_by_role',
        'notes',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Request relationship
     */
    public function request()
    {
        return $this->belongsTo(PickupRequest::class, 'request_id');
    }

    /**
     * User who made the change
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Get all status changes for a request
     */
    public static function getHistoryForRequest($requestId)
    {
        return self::where('request_id', $requestId)
            ->orderBy('created_at', 'asc')
            ->with('changedBy')
            ->get();
    }

    /**
     * Log a status change
     */
    public static function logStatusChange(
        $requestId,
        $oldStatus,
        $newStatus,
        $userId,
        $role,
        $notes = null,
        $metadata = null
    ) {
        return self::create([
            'request_id' => $requestId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_user_id' => $userId,
            'changed_by_role' => $role,
            'notes' => $notes,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
