<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\ChannelPartner;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ChannelPartnerOnboardingController extends Controller
{
    use ApiResponseTrait;

    /**
     * Submit a registration request to become a channel partner.
     */
    public function registrationRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/|unique:channel_partners,phone',
            'email' => 'required|email|max:255|unique:channel_partners,email',
            'aadhaar_number' => 'required|string|max:20|unique:channel_partners,aadhaar_number',
            'pan_number' => 'required|string|max:20|unique:channel_partners,pan_number',
            'gst_number' => 'nullable|string|max:20',
            'business_name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'pincode' => 'required|string|max:10',
            'opening_location_name' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $partner = ChannelPartner::create(array_merge($request->all(), [
                'registration_status' => 'pending',
            ]));

            // Create an approval request for admin visibility
            ApprovalRequest::create([
                'entity_type' => 'channel_partner_registration',
                'entity_id' => $partner->id,
                'request_type' => 'create',
                'payload' => $request->all(),
                'status' => 'pending',
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return $this->successResponse('partner.registration_submitted', [
                'id' => $partner->id,
                'status' => $partner->registration_status,
                'message' => 'Your registration request has been submitted and is under review.'
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Check registration status by phone or email.
     */
    public function registrationStatus(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', // phone or email
        ]);

        $partner = ChannelPartner::where('phone', $request->identifier)
            ->orWhere('email', $request->identifier)
            ->first();

        if (!$partner) {
            return $this->errorResponse('partner.not_found', 404);
        }

        return $this->successResponse('partner.status_fetched', [
            'id' => $partner->id,
            'full_name' => $partner->full_name,
            'business_name' => $partner->business_name,
            'status' => $partner->registration_status,
            'admin_remark' => $partner->admin_remark,
            'rejection_reason' => $partner->rejection_reason,
            'fee_status' => $partner->fee_payment_status,
            'created_at' => $partner->created_at->toDateTimeString(),
        ]);
    }

    /**
     * Channel Partner onboards a Pickup Boy.
     */
    public function onboardPickupBoy(Request $request)
    {
        $user = Auth::user();
        if (!$user->channel_partner_id) {
            return $this->errorResponse('partner.unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/|unique:users,phone',
            'email' => 'required|email|max:255|unique:users,email',
            'vehicle_number' => 'nullable|string|max:20',
            'city_id' => 'required|exists:cities,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $approvalRequest = ApprovalRequest::create([
            'channel_partner_id' => $user->channel_partner_id,
            'entity_type' => 'pickup_boy',
            'request_type' => 'create',
            'payload' => $request->all(),
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        return $this->successResponse('partner.pickup_boy_onboarding_submitted', $approvalRequest, 201);
    }

    /**
     * Channel Partner onboards a Warehouse.
     */
    public function onboardWarehouse(Request $request)
    {
        $user = Auth::user();
        $partner = ChannelPartner::find($user->channel_partner_id);

        if (!$partner) {
            return $this->errorResponse('partner.unauthorized', 403);
        }

        // Check warehouse limit
        $currentWarehouses = $partner->warehouses()->count();
        $pendingWarehouseRequests = ApprovalRequest::where('channel_partner_id', $partner->id)
            ->where('entity_type', 'warehouse')
            ->where('status', 'pending')
            ->count();

        if (($currentWarehouses + $pendingWarehouseRequests) >= $partner->warehouse_limit) {
            return $this->errorResponse('partner.warehouse_limit_reached', 400, [
                'limit' => $partner->warehouse_limit,
                'current' => $currentWarehouses,
                'pending' => $pendingWarehouseRequests
            ]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'pincode' => 'required|string|max:10',
            'capacity_kg' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $approvalRequest = ApprovalRequest::create([
            'channel_partner_id' => $partner->id,
            'entity_type' => 'warehouse',
            'request_type' => 'create',
            'payload' => $request->all(),
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        return $this->successResponse('partner.warehouse_onboarding_submitted', $approvalRequest, 201);
    }

    /**
     * List all onboarding requests for the Channel Partner.
     */
    public function onboardingRequests(Request $request)
    {
        $user = Auth::user();
        $query = ApprovalRequest::where('channel_partner_id', $user->channel_partner_id)
            ->whereIn('entity_type', ['pickup_boy', 'warehouse']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->entity_type) {
            $query->where('entity_type', $request->entity_type);
        }

        $paginator = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginatedResponse('partner.onboarding_requests_fetched', $paginator);
    }
}
