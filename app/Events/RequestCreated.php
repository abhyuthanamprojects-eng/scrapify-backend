<?php

namespace App\Events;

use App\Models\PickupRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;

class RequestCreated
{
    use Dispatchable, InteractsWithSockets;

    public PickupRequest $request;

    public function __construct(PickupRequest $request)
    {
        $this->request = $request;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->request->customer_id),
        ];
    }
}
