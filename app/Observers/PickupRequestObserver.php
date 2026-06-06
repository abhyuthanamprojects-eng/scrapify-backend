<?php

namespace App\Observers;

use App\Models\PickupRequest;
use App\Notifications\PickupStatusNotification;
use Illuminate\Support\Facades\Log;

class PickupRequestObserver
{
    /**
     * Handle the PickupRequest "created" event.
     */
    public function created(PickupRequest $pickupRequest): void
    {
        // Notify customer that request is booked
        if ($pickupRequest->customer) {
            $pickupRequest->customer->notify(new PickupStatusNotification($pickupRequest, 'pending'));
        }
    }

    /**
     * Handle the PickupRequest "updated" event.
     */
    public function updated(PickupRequest $pickupRequest): void
    {
        // 1. Handle Status Changes
        if ($pickupRequest->wasChanged('status')) {
            $newStatus = $pickupRequest->status;
            
            // Notify Customer of any status change
            if ($pickupRequest->customer) {
                $pickupRequest->customer->notify(new PickupStatusNotification($pickupRequest, $newStatus));

                // If completed, also send feedback request
                if ($newStatus === 'completed') {
                    $pickupRequest->customer->notify(new PickupStatusNotification($pickupRequest, 'feedback_request'));
                }
            }

            // Notify Pickup Boy if status is 'assigned' or 'reassigned'
            if (in_array($newStatus, ['assigned', 'reassigned'])) {
                // Ensure we have the latest assignment
                $assignment = $pickupRequest->assignment()->latest()->first();
                if ($assignment && $assignment->pickupBoy) {
                    $assignment->pickupBoy->notify(new PickupStatusNotification($pickupRequest, 'assigned'));
                }
            }
        }

        // 2. Handle Rescheduling (when status doesn't change but date does)
        if ($pickupRequest->wasChanged('scheduled_at') && !$pickupRequest->wasChanged('status')) {
            if ($pickupRequest->customer) {
                $pickupRequest->customer->notify(new PickupStatusNotification($pickupRequest, 'rescheduled'));
            }
        }
    }
}
