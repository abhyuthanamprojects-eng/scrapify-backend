<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use HasFactory, \App\Traits\BelongsToPartner;

    protected $fillable = [
        'channel_partner_id',
        'entity_type',
        'entity_id',
        'request_type',
        'payload',
        'attachments',
        'status',
        'admin_remarks',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attachments' => 'array',
        'approved_at' => 'datetime',
    ];

    public function channelPartner()
    {
        return $this->belongsTo(ChannelPartner::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
