<?php

namespace App\Listeners;

use App\Events\PaymentPending;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentPendingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentPending $event): void
    {
        $request = $event->request;

        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'payment_pending',
            'title' => 'Payment Pending',
            'message' => "Items verified at warehouse. Payment of ₹{$request->estimated_amount} is pending.",
            'data' => [
                'request_id' => $request->id,
                'amount' => $request->estimated_amount,
                'status' => 'payment_pending',
            ],
        ]);
    }
}
