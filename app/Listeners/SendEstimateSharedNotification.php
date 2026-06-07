<?php

namespace App\Listeners;

use App\Events\EstimateShared;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendEstimateSharedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EstimateShared $event): void
    {
        $request = $event->request;
        $estimate = $event->estimate;

        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'estimate_shared',
            'title' => 'Estimate Ready',
            'message' => "Your booking estimate of ₹{$estimate->estimated_amount} is ready for review.",
            'data' => [
                'request_id' => $request->id,
                'estimate_id' => $estimate->id,
                'estimated_amount' => $estimate->estimated_amount,
            ],
        ]);
    }
}
