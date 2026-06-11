<?php

namespace App\Observers;

use App\Models\PickupRequest;
use App\Notifications\PickupStatusNotification;
use Illuminate\Support\Facades\Log;

class PickupRequestObserver
{
    /**
     * Handle the PickupRequest "created" event.
     */
    public function created(PickupRequest $pickupRequest): void
    {
        // Notify customer that request is booked
        if ($pickupRequest->customer) {
            $pickupRequest->customer->notify(new PickupStatusNotification($pickupRequest, 'pending'));
        }
    }

    /**
     * Handle the PickupRequest "updated" event.
     */
    public function updated(PickupRequest $pickupRequest): void
    {
        // 1. Handle Status Changes
        if ($pickupRequest->wasChanged('status')) {
            $newStatus = $pickupRequest->status;
            
            // Notify Customer of any status change
            if ($pickupRequest->customer) {
                $pickupRequest->customer->notify(new PickupStatusNotification($pickupRequest, $newStatus));

                // If completed, also send feedback request
                if ($newStatus === 'completed') {
                    $pickupRequest->customer->notify(new PickupStatusNotification($pickupRequest, 'feedback_request'));
                }
            }

            // Notify Pickup Boy if status is 'assigned' or 'reassigned'
            if (in_array($newStatus, ['assigned', 'reassigned'])) {
                // Ensure we have the latest assignment
                $assignment = $pickupRequest->assignment()->latest()->first();
                if ($assignment && $assignment->pickupBoy) {
                    $assignment->pickupBoy->notify(new PickupStatusNotification($pickupRequest, 'assigned'));
                }
            }
        }

        // 2. Handle Rescheduling (when status doesn't change but date does)
        if ($pickupRequest->wasChanged('scheduled_at') && !$pickupRequest->wasChanged('status')) {
            if ($pickupRequest->customer) {
                $pickupRequest->customer->notify(new PickupStatusNotification($pickupRequest, 'rescheduled'));
            }
        }

        // 3. Handle Payment Completion Notification
        if ($pickupRequest->wasChanged('payment_status') && $pickupRequest->payment_status === 'completed') {
            try {
                $customerName = $pickupRequest->customer->name ?? 'N/A';
                $customerPhone = $pickupRequest->customer->phone ?? 'N/A';
                $finalAmount = $pickupRequest->final_amount ?? 0;
                $reference = $pickupRequest->payment_reference ?? 'N/A';
                $completedAt = $pickupRequest->payment_completed_at ? $pickupRequest->payment_completed_at->toDateTimeString() : now()->toDateTimeString();

                \Illuminate\Support\Facades\Mail::raw(
                    "Payment Confirmation Notification:\n\n" .
                    "Pickup ID: #{$pickupRequest->id}\n" .
                    "Pickup Code: {$pickupRequest->pickup_code}\n" .
                    "Customer Name: {$customerName}\n" .
                    "Customer Phone: {$customerPhone}\n" .
                    "Final Amount Paid: ₹{$finalAmount}\n" .
                    "Payment Reference / Txn ID: {$reference}\n" .
                    "Completed At: {$completedAt}",
                    function ($message) use ($pickupRequest) {
                        $message->to('account.team@scrapi5.com')
                            ->subject("Payment Completed: Pickup #{$pickupRequest->id} ({$pickupRequest->pickup_code})");
                    }
                );
            } catch (\Exception $mailEx) {
                Log::error('Failed to send payment confirmation email: ' . $mailEx->getMessage());
            }
        }
    }
}
