<?php

namespace App\Listeners;

use App\Events\DonationCompleted;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDonationCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DonationCompleted $event): void
    {
        $donation = $event->donation;

        Notification::create([
            'user_id' => $donation->customer_id,
            'type' => 'donation_completed',
            'title' => 'Donation Complete',
            'message' => 'Your donation has been received and verified at the warehouse. Thank you for your contribution!',
            'data' => [
                'donation_id' => $donation->id,
                'status' => 'completed',
            ],
        ]);
    }
}
