<?php

namespace App\Services;

use App\Models\PickupPriceLog;
use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PickupPriceService
{
    /**
     * Modify pickup final price (allowed only before lock).
     *
     * @return array{ok:bool, message:string, pickup?:PickupRequest}
     */
    public function modify(PickupRequest $pickup, float $newAmount, User $user, string $userType, ?string $reason = null): array
    {
        if ($pickup->isPriceLocked()) {
            return ['ok' => false, 'message' => 'pickup.price_locked'];
        }

        return DB::transaction(function () use ($pickup, $newAmount, $user, $userType, $reason) {
            $old = $pickup->final_amount;

            PickupPriceLog::create([
                'pickup_request_id' => $pickup->id,
                'old_amount'        => $old,
                'new_amount'        => $newAmount,
                'modified_by'       => $user->id,
                'modified_by_type'  => $userType,
                'reason'            => $reason,
            ]);

            $pickup->update([
                'final_amount'             => $newAmount,
                'final_amount_modified_by' => $user->id,
            ]);

            return ['ok' => true, 'message' => 'pickup.price_updated', 'pickup' => $pickup->fresh()];
        });
    }

    public function lock(PickupRequest $pickup): void
    {
        if (!$pickup->isPriceLocked()) {
            $pickup->update(['price_locked_at' => now()]);
        }
    }
}
