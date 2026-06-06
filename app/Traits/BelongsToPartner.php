<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToPartner
{
    /**
     * Boot the trait to apply global scope for multi-tenancy.
     */
    protected static function bootBelongsToPartner()
    {
        static::addGlobalScope('partner_isolation', function (Builder $builder) {
            if (Auth::hasUser()) {
                $user = Auth::user();
                
                if ($user->hasRole('channel_partner')) {
                    // If the user is a channel partner, restrict data to their own partner id
                    // Note: The User model itself uses channel_partner_id for pickup boys.
                    // The ChannelPartner user themselves might have a channel_partner_id or we use their CP profile.
                    
                    $partnerId = $user->channel_partner_id;
                    
                    if ($partnerId) {
                        $builder->where('channel_partner_id', $partnerId);
                    } else {
                        // Fail safe: if no partner_id, show nothing
                        $builder->whereRaw('1=0');
                    }
                }
            }
        });
    }

    /**
     * Relationship to ChannelPartner
     */
    public function channelPartner()
    {
        return $this->belongsTo(\App\Models\ChannelPartner::class);
    }
}
