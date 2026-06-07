<?php

namespace App\Events;

use App\Models\PickupRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;

class DonationCompleted
{
    use Dispatchable, InteractsWithSockets;

    public PickupRequest $donation;

    public function __construct(PickupRequest $donation)
    {
        $this->donation = $donation;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->donation->customer_id),
        ];
    }
}
