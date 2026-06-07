<?php

namespace App\Listeners;

use App\Events\PickupBoyAssigned;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPickupBoyAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PickupBoyAssigned $event): void
    {
        $request = $event->request;
        $pickupBoy = $event->pickupBoy;

        // Notification to customer
        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'pickup_boy_assigned',
            'title' => 'Pickup Partner Assigned',
            'message' => "Pickup partner {$pickupBoy->name} has been assigned for your request.",
            'data' => [
                'request_id' => $request->id,
                'pickup_boy_id' => $pickupBoy->id,
                'pickup_boy_name' => $pickupBoy->name,
                'pickup_boy_phone' => $pickupBoy->phone,
            ],
        ]);

        // Notification to pickup boy
        Notification::create([
            'user_id' => $pickupBoy->id,
            'type' => 'assignment',
            'title' => 'New Pickup Assigned',
            'message' => "New pickup request assigned: {$request->customer_name} - {$request->address}",
            'data' => [
                'request_id' => $request->id,
                'customer_name' => $request->customer_name,
                'scheduled_at' => $request->scheduled_at,
            ],
        ]);
    }
}
