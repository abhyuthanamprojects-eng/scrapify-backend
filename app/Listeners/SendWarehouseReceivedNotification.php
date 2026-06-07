<?php

namespace App\Listeners;

use App\Events\WarehouseReceived;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWarehouseReceivedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(WarehouseReceived $event): void
    {
        $request = $event->request;

        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'warehouse_received',
            'title' => 'Items Received at Warehouse',
            'message' => 'Your items have been verified and received at the warehouse.',
            'data' => [
                'request_id' => $request->id,
                'status' => 'warehouse_received',
                'warehouse_name' => $request->warehouse?->name,
            ],
        ]);
    }
}
