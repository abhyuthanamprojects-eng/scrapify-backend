<?php

namespace App\Http\Controllers\Api\PickupBoy;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\PickupRequest;
use App\Models\Settlement;


use App\Services\ActivityLogger;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
    use ApiResponseTrait;

    /**
     * List assigned pickups.
     */
    public function index(Request $request)
    {
        $assignments = Assignment::where('pickup_boy_id', $request->user()->id)
            ->with(['pickupRequest.customer', 'pickupRequest.items']) // Eager load request and customer
            ->latest()
            ->paginate(10);

        return $this->successResponse('general.success', $assignments);
    }

    /**
     * Update assignment status.
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected,completed',
            'notes' => 'nullable|string',
            'final_amount' => 'nullable|numeric|min:0', // Required if status is completed?
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $assignment = Assignment::where('pickup_boy_id', $request->user()->id)->find($id);

        if (!$assignment) {
            return $this->errorResponse('general.not_found', 404);
        }

        try {
            DB::beginTransaction();

            $assignment->status = $request->status;
            if ($request->has('notes')) {
                $assignment->notes = $request->notes;
            }
            $assignment->save();

            // Update linked Pickup Request status
            $pickupRequest = $assignment->pickupRequest;

            if ($request->status === 'accepted') {
                $pickupRequest->status = 'assigned'; // Confirmed assignment
            } elseif ($request->status === 'completed') {
                $pickupRequest->status = 'picked_up';
                $pickupRequest->pickup_completed_at = now();
                if ($request->has('final_amount')) {
                    $pickupRequest->final_amount = $request->final_amount;
                }
            } elseif ($request->status === 'on_the_way') { // If we add this to validation
                $pickupRequest->status = 'on_the_way';
            }

            $pickupRequest->save();

            // Send notification to customer
            $customer = $pickupRequest->customer;
            // Notification handled by PickupRequestObserver via $pickupRequest->save()

            // Create settlement for channel partner if applicable
            if ($request->status === 'completed' && $customer && $customer->hasRole('channel_partner')) {
                $finalAmount = $pickupRequest->final_amount ?? $pickupRequest->estimated_amount;
                $commissionRate = 10.00; // Default 10% commission
                $commissionAmount = Settlement::calculateCommission($finalAmount, $commissionRate);

                Settlement::create([
                    'partner_id' => $customer->id,
                    'pickup_request_id' => $pickupRequest->id,
                    'total_amount' => $finalAmount,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $commissionAmount,
                    'status' => 'pending'
                ]);
            }

            ActivityLogger::log('update', 'pickup', 'Assignment status updated to ' . $request->status, ['assignment_id' => $id, 'status' => $request->status]);

            DB::commit();

            return $this->successResponse('general.updated', $assignment->load('pickupRequest'));

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }
}
