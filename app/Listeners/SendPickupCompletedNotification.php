<?php

namespace App\Listeners;

use App\Events\PickupCompleted;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPickupCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PickupCompleted $event): void
    {
        $request = $event->request;

        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'pickup_completed',
            'title' => 'Pickup Completed',
            'message' => 'Your items have been picked up successfully. They are now in transit to the warehouse.',
            'data' => [
                'request_id' => $request->id,
                'status' => 'pickup_completed',
            ],
        ]);
    }
}
