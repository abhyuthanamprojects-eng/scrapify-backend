<?php

namespace App\Listeners;

use App\Events\EstimateApproved;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendEstimateApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(EstimateApproved $event): void
    {
        $request = $event->request;
        $estimate = $event->estimate;

        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'estimate_approved',
            'title' => 'Estimate Approved',
            'message' => "You approved the estimate of ₹{$estimate->estimated_amount}. Your request is now ready for pickup scheduling.",
            'data' => [
                'request_id' => $request->id,
                'estimate_id' => $estimate->id,
            ],
        ]);
    }
}
