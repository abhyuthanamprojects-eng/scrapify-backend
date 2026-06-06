<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    protected $fillable = [
        'partner_id',
        'pickup_request_id',
        'total_amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'status',
        'payout_status',
        'payment_id',
        'payout_date',
        'payment_proof',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payout_date' => 'date',
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }

    public function pickupRequest()
    {
        return $this->belongsTo(PickupRequest::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Calculate commission amount based on total and rate.
     */
    public static function calculateCommission(float $totalAmount, float $commissionRate): float
    {
        return round(($totalAmount * $commissionRate) / 100, 2);
    }
}
