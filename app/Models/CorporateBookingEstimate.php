<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateBookingEstimate extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'estimated_amount',
        'estimated_weight',
        'estimated_items_count',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'estimated_amount' => 'decimal:2',
        'estimated_weight' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Pickup request relationship
     */
    public function request()
    {
        return $this->belongsTo(PickupRequest::class, 'request_id');
    }

    /**
     * User who created the estimate
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved the estimate
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if estimate is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if estimate is shared
     */
    public function isShared(): bool
    {
        return $this->status === 'shared';
    }

    /**
     * Check if estimate is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if estimate is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the estimate
     */
    public function approve($userId, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Reject the estimate
     */
    public function reject($userId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Share estimate with customer
     */
    public function share()
    {
        $this->update(['status' => 'shared']);
        return $this;
    }

    /**
     * Get the latest estimate for a request
     */
    public static function latestForRequest($requestId)
    {
        return self::where('request_id', $requestId)
            ->latest('created_at')
            ->first();
    }
}
