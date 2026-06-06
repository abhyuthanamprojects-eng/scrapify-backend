<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\Payment;
use App\Models\PickupRequest;
use App\Models\PickupStatusLog;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettlementController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all settlements with filters.
     */
    public function index(Request $request)
    {
        $query = Settlement::with(['partner', 'pickupRequest', 'payment']);

        // Filter by status
        if ($request->has('status')) {
            $query->where(fn($q) => $q->where('status', $request->status)->orWhere('payout_status', $request->status));
        }

        // Filter by partner
        if ($request->has('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        $settlements = $query->latest()->paginate(20);

        return $this->successResponse('settlements.fetched', $settlements);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required|exists:users,id',
            'pickup_request_id' => 'required|exists:pickup_requests,id',
            'total_amount' => 'required|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'payout_status' => 'nullable|in:pending,processing,paid,hold,rejected',
            'payout_date' => 'nullable|date',
            'payment_proof' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $rate = (float) ($request->commission_rate ?? 10);
        $commission = Settlement::calculateCommission((float) $request->total_amount, $rate);
        $settlement = Settlement::create([
            'partner_id' => $request->partner_id,
            'pickup_request_id' => $request->pickup_request_id,
            'total_amount' => $request->total_amount,
            'commission_rate' => $rate,
            'commission_amount' => $commission,
            'net_amount' => round($request->total_amount - $commission, 2),
            'status' => $request->payout_status === 'rejected' ? 'rejected' : 'pending',
            'payout_status' => $request->payout_status ?? 'pending',
            'payout_date' => $request->payout_date,
            'payment_proof' => $request->payment_proof,
            'notes' => $request->notes,
        ]);

        return $this->successResponse('settlement.created', $settlement, 201);
    }

    public function update(Request $request, $id)
    {
        $settlement = Settlement::find($id);
        if (!$settlement) {
            return $this->errorResponse('settlement.not_found', 404);
        }

        $validator = Validator::make($request->all(), [
            'total_amount' => 'sometimes|numeric|min:0',
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
            'payout_status' => 'sometimes|in:pending,processing,paid,hold,rejected',
            'payout_date' => 'nullable|date',
            'payment_proof' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $total = (float) ($request->total_amount ?? $settlement->total_amount);
        $rate = (float) ($request->commission_rate ?? $settlement->commission_rate);
        $commission = Settlement::calculateCommission($total, $rate);
        $payload = $validator->validated() + [
            'total_amount' => $total,
            'commission_rate' => $rate,
            'commission_amount' => $commission,
            'net_amount' => round($total - $commission, 2),
        ];

        if (isset($payload['payout_status'])) {
            $payload['status'] = $payload['payout_status'] === 'paid' ? 'paid' : ($payload['payout_status'] === 'rejected' ? 'rejected' : $settlement->status);
        }

        $settlement->update($payload);

        return $this->successResponse('settlement.updated', $settlement->fresh());
    }

    /**
     * Get settlement details.
     */
    public function show($id)
    {
        $settlement = Settlement::with(['partner', 'pickupRequest', 'payment'])->find($id);

        if (!$settlement) {
            return $this->errorResponse('settlement.not_found', 404);
        }

        return $this->successResponse('settlement.fetched', $settlement);
    }

    /**
     * Approve settlement.
     */
    public function approve(Request $request, $id)
    {
        $settlement = Settlement::find($id);

        if (!$settlement) {
            return $this->errorResponse('settlement.not_found', 404);
        }

        if ($settlement->status !== 'pending') {
            return $this->errorResponse('settlement.already_processed', 400);
        }

        try {
            DB::beginTransaction();

            $settlement->update([
                'status' => 'approved',
                'payout_status' => 'processing',
                'notes' => $request->notes ?? $settlement->notes
            ]);

            DB::commit();

            return $this->successResponse('settlement.approved', $settlement);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Mark settlement as paid and create payment record.
     */
    public function markAsPaid(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'payment_type' => 'required|in:bank_transfer,upi,cash',
            'proof_image_path' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $settlement = Settlement::find($id);

        if (!$settlement) {
            return $this->errorResponse('settlement.not_found', 404);
        }

        if ($settlement->status === 'paid') {
            return $this->errorResponse('settlement.already_paid', 400);
        }

        try {
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'user_id' => $settlement->partner_id,
                'pickup_request_id' => $settlement->pickup_request_id,
                'amount' => $settlement->net_amount,
                'transaction_id' => $request->transaction_id,
                'status' => 'completed',
                'type' => $request->payment_type,
                'proof_image_path' => $request->proof_image_path,
                'remarks' => $request->notes ?? 'Partner settlement payment'
            ]);

            // Update settlement
            $settlement->update([
                'status' => 'paid',
                'payout_status' => 'paid',
                'payment_id' => $payment->id,
                'payout_date' => now()->toDateString(),
                'payment_proof' => $request->proof_image_path,
                'notes' => $request->notes ?? $settlement->notes
            ]);

            // Mark the linked pickup request as fully completed now that settlement is paid
            if ($settlement->pickup_request_id) {
                $pickupRequest = PickupRequest::find($settlement->pickup_request_id);
                if ($pickupRequest && $pickupRequest->status === 'picked_up') {
                    $pickupRequest->update(['status' => 'completed']);

                    PickupStatusLog::create([
                        'pickup_request_id' => $pickupRequest->id,
                        'status' => 'completed',
                        'notes' => 'Marked completed after partner settlement paid.',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return $this->successResponse('settlement.marked_as_paid', $settlement->load('payment'));

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Reject settlement.
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $settlement = Settlement::find($id);

        if (!$settlement) {
            return $this->errorResponse('settlement.not_found', 404);
        }

        try {
            DB::beginTransaction();

            $settlement->update([
                'status' => 'rejected',
                'payout_status' => 'rejected',
                'notes' => $request->notes
            ]);

            DB::commit();

            return $this->successResponse('settlement.rejected', $settlement);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Get partner settlement summary.
     */
    public function partnerSummary($partnerId)
    {
        $summary = [
            'total_settlements' => Settlement::where('partner_id', $partnerId)->count(),
            'pending_amount' => Settlement::where('partner_id', $partnerId)
                ->whereIn('payout_status', ['pending', 'processing', 'hold'])
                ->sum('net_amount'),
            'approved_amount' => Settlement::where('partner_id', $partnerId)
                ->where('status', 'approved')
                ->sum('net_amount'),
            'paid_amount' => Settlement::where('partner_id', $partnerId)
                ->where('payout_status', 'paid')
                ->sum('net_amount'),
            'total_commission_earned' => Settlement::where('partner_id', $partnerId)
                ->whereIn('status', ['approved', 'paid'])
                ->sum('commission_amount'),
        ];

        return $this->successResponse('settlement.summary_fetched', $summary);
    }
}
