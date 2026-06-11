<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Models\PickupRequest;
use App\Services\RequestStatusTransitionService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    /**
     * Move request to payment pending (warehouse action)
     */
    public function moveToPaymentPending($id)
    {
        $request = PickupRequest::findOrFail($id);

        // Only warehouse/admin can move to payment pending
        if (!Auth::user()->hasRole(['admin', 'warehouse'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be in warehouse_received status
        $status = RequestStatus::tryFrom($request->status_new);
        if ($status !== RequestStatus::WAREHOUSE_RECEIVED) {
            return $this->errorResponse('payment.invalid_status_for_payment', 400);
        }

        // Only for scrap/corporate (not donation)
        if (!in_array($request->request_type, ['scrap', 'corporate'])) {
            return $this->errorResponse('payment.payment_not_applicable', 400);
        }

        try {
            $request->transitionTo(
                RequestStatus::PAYMENT_PENDING,
                Auth::id(),
                'warehouse',
                'Moved to payment pending'
            );

            $request->update(['payment_pending_at' => now()]);

            event(new \App\Events\PaymentPending($request));

            return $this->successResponse('payment.moved_to_pending', [
                'request_id' => $request->id,
                'status' => RequestStatus::PAYMENT_PENDING->value,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('payment.transition_failed', 400, $e->getMessage());
        }
    }

    /**
     * Process payment (admin action)
     */
    public function processPayment(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);

        // Only admin/payment_admin can process payment
        if (!Auth::user()->hasAnyRole(['admin', 'payment_admin'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be in payment_pending status
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if ($status !== RequestStatus::PAYMENT_PENDING) {
            return $this->errorResponse('payment.invalid_status_for_processing', 400);
        }

        $validated = $request->validate([
            'final_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer,upi,check,cash',
            'payment_reference' => 'required|string|max:100',
            'receiver_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // Update payment details and status
            $pickupRequest->update([
                'final_amount' => $validated['final_amount'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
                'receiver_name' => $validated['receiver_name'] ?? null,
                'payment_status' => 'processing',
            ]);

            // Transition request
            $pickupRequest->transitionTo(
                RequestStatus::PAYMENT_PROCESSING,
                Auth::id(),
                Auth::user()?->hasRole('admin') ? 'admin' : 'payment_admin',
                "Payment processing: {$validated['payment_reference']}"
            );

            return $this->successResponse('payment.processing', [
                'request_id' => $pickupRequest->id,
                'status' => RequestStatus::PAYMENT_PROCESSING->value,
                'final_amount' => $validated['final_amount'],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('payment.processing_failed', 400, $e->getMessage());
        }
    }

    /**
     * Confirm payment completed (admin action)
     */
    public function confirmPayment(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);

        // Only admin/payment_admin can confirm payment
        if (!Auth::user()->hasAnyRole(['admin', 'payment_admin'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Must be in payment_processing status
        $status = RequestStatus::tryFrom($pickupRequest->status_new);
        if ($status !== RequestStatus::PAYMENT_PROCESSING) {
            return $this->errorResponse('payment.invalid_status_for_confirmation', 400);
        }

        $validated = $request->validate([
            'transaction_number' => 'required|string|max:100',
            'payment_receipt_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $imagePath = null;
            if ($request->hasFile('payment_receipt_image')) {
                $imagePath = $request->file('payment_receipt_image')->store('payment_receipts', 'public');
            }

            // Update payment completion
            $pickupRequest->update([
                'payment_status' => 'completed',
                'payment_reference' => $validated['transaction_number'],
                'payment_receipt_image' => $imagePath,
                'payment_completed_at' => now(),
                'completed_at' => now(),
            ]);

            // Also update the Payment model record if it exists
            $payment = \App\Models\Payment::where('pickup_request_id', $pickupRequest->id)->latest()->first();
            if ($payment) {
                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => $validated['transaction_number'],
                    'proof_image_path' => $imagePath,
                ]);
            }

            // Transition request to completed
            $pickupRequest->transitionTo(
                RequestStatus::COMPLETED,
                Auth::id(),
                Auth::user()?->hasRole('admin') ? 'admin' : 'payment_admin',
                $validated['notes'] ?? 'Payment confirmed and request completed'
            );

            event(new \App\Events\PaymentCompleted($pickupRequest));

            return $this->successResponse('payment.completed', [
                'request_id' => $pickupRequest->id,
                'status' => RequestStatus::COMPLETED->value,
                'payment_status' => 'completed',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('payment.confirmation_failed', 400, $e->getMessage());
        }
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails($id)
    {
        $request = PickupRequest::findOrFail($id);

        // Authorization: customer, admin, warehouse, payment_admin
        $user = Auth::user();
        if ($request->customer_id !== $user->id && !$user->hasAnyRole(['admin', 'warehouse', 'payment_admin'])) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        return $this->successResponse('payment.details_fetched', [
            'request_id' => $request->id,
            'payment_status' => $request->payment_status,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'payment_receipt_image' => $request->payment_receipt_image ? Storage::disk('public')->url($request->payment_receipt_image) : null,
            'estimated_amount' => $request->estimated_amount,
            'final_amount' => $request->final_amount,
            'payment_pending_at' => $request->payment_pending_at?->format('Y-m-d H:i:s'),
            'payment_completed_at' => $request->payment_completed_at?->format('Y-m-d H:i:s'),
        ]);
    }
}
