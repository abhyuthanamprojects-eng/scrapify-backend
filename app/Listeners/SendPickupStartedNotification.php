<?php

namespace App\Listeners;

use App\Events\PickupStarted;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPickupStartedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PickupStarted $event): void
    {
        $request = $event->request;
        $pickupBoy = $request->currentAssignment?->pickupBoy;

        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'pickup_started',
            'title' => 'Pickup In Progress',
            'message' => "{$pickupBoy?->name} is on the way to pick up your items.",
            'data' => [
                'request_id' => $request->id,
                'status' => 'pickup_started',
            ],
        ]);
    }
}
