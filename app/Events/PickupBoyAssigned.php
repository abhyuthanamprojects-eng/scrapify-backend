<?php

namespace App\Events;

use App\Models\PickupRequest;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;

class PickupBoyAssigned
{
    use Dispatchable, InteractsWithSockets;

    public PickupRequest $request;
    public User $pickupBoy;

    public function __construct(PickupRequest $request, User $pickupBoy)
    {
        $this->request = $request;
        $this->pickupBoy = $pickupBoy;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->request->customer_id),
            new PrivateChannel('user.' . $this->pickupBoy->id),
        ];
    }
}
