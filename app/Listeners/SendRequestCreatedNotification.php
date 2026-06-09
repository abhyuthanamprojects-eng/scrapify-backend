<?php

namespace App\Listeners;

use App\Events\RequestCreated;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendRequestCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(RequestCreated $event): void
    {
        $request = $event->request;

        // Send notification to customer
        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'request_created',
            'title' => 'Request Created Successfully',
            'message' => "Your {$request->request_type} request (#{$request->pickup_code}) has been created and is awaiting warehouse assignment.",
            'data' => [
                'request_id' => $request->id,
                'request_type' => $request->request_type,
                'pickup_code' => $request->pickup_code,
            ],
        ]);

        // Send notification to warehouse
        if ($request->warehouse_id) {
            $warehouseUserIds = \App\Models\User::where('warehouse_id', $request->warehouse_id)
                ->pluck('id')
                ->toArray();
                
            $warehouseManagerId = $request->warehouse->manager_id ?? null;
            if ($warehouseManagerId && !in_array($warehouseManagerId, $warehouseUserIds)) {
                $warehouseUserIds[] = $warehouseManagerId;
            }

            foreach ($warehouseUserIds as $wUserId) {
                Notification::create([
                    'user_id' => $wUserId,
                    'type' => 'new_request',
                    'title' => 'New Request Assigned',
                    'message' => "New {$request->request_type} request received: {$request->customer_name}",
                    'data' => [
                        'request_id' => $request->id,
                        'request_type' => $request->request_type,
                    ],
                ]);
            }
        }
    }
}
