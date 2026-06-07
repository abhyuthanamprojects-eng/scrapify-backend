<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentCompleted $event): void
    {
        $request = $event->request;

        Notification::create([
            'user_id' => $request->customer_id,
            'type' => 'payment_completed',
            'title' => 'Payment Completed',
            'message' => "Payment of ₹{$request->final_amount} has been successfully processed. Your request is now complete!",
            'data' => [
                'request_id' => $request->id,
                'amount' => $request->final_amount,
                'status' => 'completed',
            ],
        ]);
    }
}
