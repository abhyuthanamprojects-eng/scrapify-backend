<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'partner_id',
        'amount',
        'status',
        'transaction_id',
        'bank_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'admin_notes'
    ];

    public function partner()
    {
        return $this->belongsTo(User::class, 'partner_id');
    }
}
