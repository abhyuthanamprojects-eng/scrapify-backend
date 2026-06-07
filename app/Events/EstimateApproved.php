<?php

namespace App\Events;

use App\Models\PickupRequest;
use App\Models\CorporateBookingEstimate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;

class EstimateApproved
{
    use Dispatchable, InteractsWithSockets;

    public PickupRequest $request;
    public CorporateBookingEstimate $estimate;

    public function __construct(PickupRequest $request, CorporateBookingEstimate $estimate)
    {
        $this->request = $request;
        $this->estimate = $estimate;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->request->customer_id),
        ];
    }
}
