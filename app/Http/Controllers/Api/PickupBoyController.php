<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\PickupRequest;
use App\Models\PickupBoyLocation;
use App\Models\PickupItem;
use App\Models\PickupImage;
use App\Models\RequestStatusLog;
use App\Http\Resources\PickupRequestResource;
use App\Services\PickupAssignmentService;
use App\Services\PickupPriceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PickupBoyController extends Controller
{
    use ApiResponseTrait;

    /**
     * List assigned pickups.
     */
    #[OA\Get(
        path: "/api/pickup-boy/assignments",
        operationId: "getPickupAssignments",
        tags: ["Pickup Boy"],
        summary: "List assigned and accepted pickups for the agent",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Assignments fetched")
        ]
    )]
    public function getAssignments(Request $request)
    {
        $status = $request->query('status', 'active'); // Default to active for agent app
        $period = strtolower((string) $request->query('period', 'today'));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = PickupRequest::whereHas('assignment', function ($q) use ($request, $status, $period) {
            $q->where('pickup_boy_id', $request->user()->id);

            // Filter by assignment status to ensure we don't show rejected/cancelled in active list
            if ($status === 'active') {
                $q->whereNotIn('status', ['rejected', 'cancelled', 'completed']);
            } elseif ($status === 'completed') {
                $q->where('status', 'completed');
                $this->applyPeriodFilter($q, 'completed_at', $period);
            }
        })->with(['customer', 'items.category', 'address', 'assignment', 'images', 'requestAttributes.attribute']);

        if ($status === 'completed') {
            $query->whereIn('status', ['picked_up', 'completed']);
        } elseif ($status === 'active') {
            $query->whereIn('status', ['pending', 'assigned', 'accepted', 'on_the_way', 'arrived', 'verifying']);
        } elseif ($status === 'history') {
            $query->whereIn('status', ['picked_up', 'completed', 'cancelled', 'rescheduled']);
        }

        if ($dateFrom)
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        if ($dateTo)
            $query->whereDate('scheduled_at', '<=', $dateTo);
        if (!$dateFrom && !$dateTo && $status !== 'completed') {
            $this->applyPeriodFilter($query, 'scheduled_at', $period);
        }

        $pickups = $query->orderBy('scheduled_at', 'asc')->paginate($request->per_page ?? 20);

        // Transform paginator items
        $pickups->getCollection()->transform(function ($pickup) {
            return new PickupRequestResource($pickup);
        });

        return $this->paginatedResponse('pickup.assignments_fetched', $pickups);
    }

    public function getProfileStatus(Request $request)
    {
        $user = $request->user()->load(['city', 'warehouses.channelPartner']);
        $assignedWarehouse = $user->warehouses()->with('channelPartner')->orderByDesc('created_at')->first();

        return $this->successResponse('profile.status_fetched', [
            'is_online' => (bool) $user->is_online,
            'is_available' => (bool) $user->is_available,
            'last_active_at' => $user->last_active_at ? $user->last_active_at->format('Y-m-d H:i:s') : null,
            'current_latitude' => $user->latitude,
            'current_longitude' => $user->longitude,
            'location_updated_at' => $user->location_updated_at ? $user->location_updated_at->format('Y-m-d H:i:s') : null,
            'vehicle_number' => $user->vehicle_number,
            'assigned_warehouse' => $assignedWarehouse,
            'channel_partner_name' => $assignedWarehouse && $assignedWarehouse->channelPartner ? $assignedWarehouse->channelPartner->name : null,
            'city' => $user->city,
            'is_enabled' => $user->status === 'active',
            'support_phone' => \App\Models\AppSetting::get('support_phone', '1800-000-0000')
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user()->load(['city', 'warehouses.channelPartner']);
        if ($user->hasRole('pickup_boy')) {
            $user->ensureEmployeeId();
        }

        // Get the assigned warehouse (first one from the pivot table, latest assignment)
        $assignedWarehouse = $user->warehouses()->with('channelPartner')->orderByDesc('created_at')->first();

        return $this->successResponse('profile.fetched', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->roles->first()?->name,
            'profile_photo' => $user->profile_photo_path,
            'profile_photo_url' => $user->profile_photo_url,
            'employee_id' => $user->employee_id,
            'vehicle_number' => $user->vehicle_number,
            'is_online' => (bool) $user->is_online,
            'is_available' => (bool) $user->is_available,
            'city' => $user->city,
            'warehouse_name' => $assignedWarehouse ? $assignedWarehouse->name : 'N/A',
            'channel_partner_name' => $assignedWarehouse && $assignedWarehouse->channelPartner ? $assignedWarehouse->channelPartner->name : null,
            'assigned_warehouse' => $assignedWarehouse,
            'status' => $user->status,
        ]);
    }

    /**
     * Get pickup details.
     */
    #[OA\Get(
        path: "/api/pickup-boy/pickups/{id}",
        operationId: "getPickupBoyDetails",
        tags: ["Pickup Boy"],
        summary: "Get specific pickup details for the agent",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Pickup details fetched")
        ]
    )]
    public function show($id)
    {
        $pickup = PickupRequest::with(['customer', 'items.category', 'images', 'assignment', 'address', 'requestAttributes.attribute', 'statusLogs'])
            ->whereHas('assignment', function ($query) {
                $query->where('pickup_boy_id', request()->user()->id)
                    ->whereNotIn('status', ['rejected', 'cancelled', 'reassigned']);
            })
            ->findOrFail($id);

        return $this->successResponse('pickup.details_fetched', new PickupRequestResource($pickup));
    }

    #[OA\Post(
        path: "/api/pickup-boy/pickups/{id}/verify",
        operationId: "verifyPickup",
        tags: ["Pickup Boy"],
        summary: "Verify items and update final price",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["items", "images[]"],
                    properties: [
                        new OA\Property(
                            property: "items",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "weight", type: "number"),
                                    new OA\Property(property: "quantity", type: "integer")
                                ]
                            )
                        ),
                        new OA\Property(
                            property: "images[]",
                            type: "array",
                            items: new OA\Items(type: "string", format: "binary")
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Pickup Verified"
            )
        ]
    )]
    public function verifyPickup(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);

        if ($pickupRequest->isPriceLocked()) {
            return $this->errorResponse('pickup.price_locked', 422);
        }

        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->where('status', '!=', 'completed') // Ensure not already completed
            ->first();

        if (!$assignment) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Donation flow: only photo + remarks. No items/price.
        if ($pickupRequest->request_type === 'donation') {
            return $this->verifyDonation($request, $pickupRequest, $assignment);
        }

        $validator = Validator::make($request->all(), [
            'verified_items' => 'required|array',
            'verified_items.*.pickup_item_id' => 'nullable|exists:pickup_items,id',
            'verified_items.*.item_id' => 'nullable', // Optional if mapped manually
            'verified_items.*.item_name' => 'nullable|string',
            'verified_items.*.weight_kg' => 'nullable|numeric|min:0',
            'verified_items.*.quantity' => 'nullable|integer|min:0',
            'verified_items.*.condition' => 'nullable|string',
            'verified_items.*.rate_per_kg' => 'nullable|numeric|min:0',
            'verified_items.*.rate_per_unit' => 'nullable|numeric|min:0',
            'verified_items.*.action' => 'required|in:added,updated,removed,verified',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
            'notes' => 'nullable|string',
            'final_payout_amount' => 'nullable|numeric|min:0',
            'verification_method' => 'nullable|string',
            'verified_items.*.final_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $totalFinalAmount = 0;

            foreach ($request->verified_items as $itemData) {
                // If removed
                if ($itemData['action'] === 'removed' && !empty($itemData['pickup_item_id'])) {
                    $item = \App\Models\PickupItem::where('pickup_request_id', $pickupRequest->id)->find($itemData['pickup_item_id']);
                    if ($item) {
                        $item->delete(); // Or mark as removed somehow, deleting is cleanest for final amount
                    }
                    continue;
                }

                // If updated or verified
                if (in_array($itemData['action'], ['updated', 'verified']) && !empty($itemData['pickup_item_id'])) {
                    $item = \App\Models\PickupItem::where('pickup_request_id', $pickupRequest->id)->find($itemData['pickup_item_id']);
                    if ($item) {
                        $item->weight = $itemData['weight_kg'] ?? $item->weight;
                        $item->quantity = $itemData['quantity'] ?? $item->quantity;

                        if (isset($itemData['condition'])) {
                            $item->condition = $itemData['condition'];
                        }

                        // Rate overrides
                        $rate = $itemData['rate_per_kg'] ?? ($itemData['rate_per_unit'] ?? $item->price_per_unit);
                        $item->price_per_unit = $rate;

                        $price = 0;
                        if (isset($itemData['final_amount'])) {
                            $price = $itemData['final_amount'];
                        } else {
                            if ($item->weight > 0) {
                                $price = $rate * $item->weight;
                            } else {
                                $price = $rate * $item->quantity;
                            }
                        }
                        $item->total_price = $price;
                        $item->save();

                        $totalFinalAmount += $price;
                    }
                }

                // If added
                if ($itemData['action'] === 'added') {
                    $rate = $itemData['rate_per_kg'] ?? ($itemData['rate_per_unit'] ?? 0);
                    $weight = $itemData['weight_kg'] ?? 0;
                    $quantity = $itemData['quantity'] ?? 0;

                    $price = 0;
                    if (isset($itemData['final_amount'])) {
                        $price = $itemData['final_amount'];
                    } else {
                        if ($weight > 0) {
                            $price = $rate * $weight;
                        } else {
                            $price = $rate * $quantity;
                        }
                    }

                    \App\Models\PickupItem::create([
                        'pickup_request_id' => $pickupRequest->id,
                        'category_id' => null, // Assuming you allow null for custom added items or you infer
                        'weight' => $weight,
                        'quantity' => $quantity,
                        'condition' => $itemData['condition'] ?? 'Good',
                        'price_per_unit' => $rate,
                        'total_price' => $price,
                        // In reality you may have an 'item_name' column or similar if it's dynamic
                    ]);

                    $totalFinalAmount += $price;
                }
            }

            // Trust frontend amount if provided and reasonable, else use calculated
            if ($request->has('final_payout_amount') && $request->final_payout_amount !== null) {
                $totalFinalAmount = $request->final_payout_amount;
            }

            // Audit log price change
            if ($pickupRequest->final_amount != $totalFinalAmount) {
                \App\Models\PickupPriceLog::create([
                    'pickup_request_id' => $pickupRequest->id,
                    'old_amount' => $pickupRequest->final_amount,
                    'new_amount' => $totalFinalAmount,
                    'modified_by' => $request->user()->id,
                    'modified_by_type' => 'pickup_boy',
                    'reason' => $request->notes,
                ]);
            }

            // Keep status as 'picked_up' so it goes through warehouse receipt and payment flow
            $newPickupStatus = 'picked_up';

            $pickupRequest->update([
                'final_amount' => $totalFinalAmount,
                'final_amount_modified_by' => $request->user()->id,
                'status' => $newPickupStatus,
                'price_locked_at' => now(),
            ]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickupRequest->id,
                'status' => $newPickupStatus,
                'notes' => $request->notes ?? 'Items verified and picked up.',
                'created_by' => $request->user()->id
            ]);

            // Store Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('pickup_images', 'public');
                    \App\Models\PickupImage::create([
                        'pickup_request_id' => $pickupRequest->id,
                        'image_path' => $path,
                        'type' => 'verification',
                    ]);
                }
            }

            $assignment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Create pending payment record
            \App\Models\Payment::create([
                'user_id' => $pickupRequest->customer_id,
                'pickup_request_id' => $pickupRequest->id,
                'amount' => $totalFinalAmount,
                'status' => 'pending',
                'type' => 'cash',
            ]);

            // Auto-create Settlement for channel partner pickups
            if ($pickupRequest->channel_partner_id) {
                $commissionRate = 10.00; // Default 10% — adjust per partner config if needed
                $commissionAmount = \App\Models\Settlement::calculateCommission($totalFinalAmount, $commissionRate);
                \App\Models\Settlement::firstOrCreate(
                    ['pickup_request_id' => $pickupRequest->id], // Prevent duplicates
                    [
                        'partner_id'        => $pickupRequest->channel_partner_id,
                        'total_amount'      => $totalFinalAmount,
                        'commission_rate'   => $commissionRate,
                        'commission_amount' => $commissionAmount,
                        'net_amount'        => round($totalFinalAmount - $commissionAmount, 2),
                        'status'            => 'pending',
                        'payout_status'     => 'pending',
                        'notes'             => 'Auto-created on pickup completion.',
                    ]
                );
            }

            // Generate Bill/Invoice fake URL and ID
            $billId = 'INV-' . time() . '-' . $pickupRequest->id;
            $invoiceUrl = url('/api/bills/' . $billId . '/download');

            \Illuminate\Support\Facades\DB::commit();

            // Notify Customer

            // Generate Bill/Invoice fake URL and ID

            return $this->successResponse('pickup.verified_success', [
                'pickup' => $pickupRequest->load(['items.category', 'images', 'assignment']),
                'bill_id' => $billId,
                'invoice_url' => $invoiceUrl,
                'final_payout_amount' => $totalFinalAmount,
                'verified_items' => $pickupRequest->items
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Reschedule Pickup.
     */
    public function reschedulePickup(Request $request, $id)
    {
        $request->validate([
            'rescheduled_at' => 'required|date|after:now',
            'reason' => 'required|string'
        ]);

        $pickupRequest = PickupRequest::findOrFail($id);

        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->firstOrFail();

        \Illuminate\Support\Facades\DB::transaction(function () use ($pickupRequest, $assignment, $request) {
            $pickupRequest->update([
                'scheduled_at' => $request->rescheduled_at,
                'reschedule_reason' => $request->reason,
                'status' => 'rescheduled'
            ]);

            $assignment->update([
                'status' => 'rescheduled'
            ]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickupRequest->id,
                'status' => 'rescheduled',
                'notes' => $request->reason,
                'created_by' => $request->user()->id
            ]);

        });

        return $this->successResponse('pickup.rescheduled');
    }

    /**
     * Accept a pickup assignment.
     */
    #[OA\Post(
        path: "/api/pickup-boy/pickups/{id}/accept",
        operationId: "acceptPickupAssignment",
        tags: ["Pickup Boy"],
        summary: "Accept an assigned pickup",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Pickup Accepted")
        ]
    )]
    public function acceptAssignment(Request $request, $id)
    {
        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->firstOrFail();

        if ($assignment->status !== 'assigned') {
            return $this->errorResponse('pickup.invalid_status_transition', 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($assignment) {
            $assignment->update(['status' => 'accepted']);
            $assignment->pickupRequest->update(['status' => 'accepted']);
        });

        return $this->successResponse('pickup.accepted_success', $assignment->load('pickupRequest'));
    }

    /**
     * Reject a pickup assignment.
     */
    #[OA\Post(
        path: "/api/pickup-boy/pickups/{id}/reject",
        operationId: "rejectPickupAssignment",
        tags: ["Pickup Boy"],
        summary: "Reject an assigned pickup",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Pickup Rejected")
        ]
    )]
    public function rejectAssignment(Request $request, $id)
    {
        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($assignment->status, ['assigned', 'accepted'])) {
            return $this->errorResponse('pickup.invalid_status_transition', 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($assignment) {
            $assignment->update(['status' => 'rejected']);
            $assignment->pickupRequest->update(['status' => 'pending']); // Put back in pool
        });

        return $this->successResponse('pickup.rejected_success');
    }

    /**
     * Update travel status (on_the_way, arrived, etc).
     */
    #[OA\Post(
        path: "/api/pickup-boy/pickups/{id}/status",
        operationId: "updatePickupTravelStatus",
        tags: ["Pickup Boy"],
        summary: "Update current travel/operations status",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["on_the_way", "arrived", "verifying"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Status Updated")
        ]
    )]
    public function updateTravelStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:on_the_way,arrived,reached_location,verifying,pickup_started,picked_up,pickup_completed,delivered_to_warehouse'
        ]);

        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->firstOrFail();

        if ($assignment->status === 'completed' || $assignment->status === 'rejected') {
            return $this->errorResponse('pickup.invalid_status_transition', 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($assignment, $request) {
            $assignment->update(['status' => $request->status]);
            $assignment->pickupRequest->update(['status' => $request->status]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $assignment->pickup_request_id,
                'status' => $request->status,
                'notes' => 'Status updated by Agent',
                'created_by' => $request->user()->id
            ]);

            // Auto-transition to warehouse when pickup completed
            if ($request->status === 'pickup_completed') {
                $pickup = $assignment->pickupRequest;
                $pickup->transitionTo(
                    \App\Enums\RequestStatus::WAREHOUSE_RECEIVE_PENDING,
                    $request->user()->id,
                    'pickup_boy',
                    'Pickup completed - awaiting warehouse receipt'
                );
            }
        });

        return $this->successResponse('pickup.status_updated', [
            'status' => $request->status
        ]);
    }

    public function startPickup(Request $request, $id)
    {
        $request->merge(['status' => 'pickup_started']);
        return $this->updateTravelStatus($request, $id);
    }


    /**
     * Update online/offline status.
     */
    #[OA\Post(
        path: "/api/pickup-boy/status",
        operationId: "toggleAgentStatus",
        tags: ["Pickup Boy"],
        summary: "Toggle agent online/offline status",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["is_online"],
                properties: [
                    new OA\Property(property: "is_online", type: "boolean")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Status Toggled")
        ]
    )]
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'is_online' => 'required|boolean',
            'is_available' => 'nullable|boolean'
        ]);

        $user = $request->user();

        // Map 'is_online' from app to 'is_manual_offline' in DB
        // If app says is_online=false, we set is_manual_offline=true
        $user->is_manual_offline = !$request->is_online;
        
        if ($request->has('is_available')) {
            $user->is_available = $request->is_available;
        }

        $user->save();

        return $this->successResponse('profile.status_updated', [
            'is_online' => (bool) $user->is_online,
            'is_available' => (bool) $user->is_available,
            'is_manual_offline' => (bool) $user->is_manual_offline
        ]);
    }
    /**
     * Update live location coordinates and vehicle info.
     */
    #[OA\Post(
        path: "/api/pickup-boy/location",
        operationId: "updateAgentLocation",
        tags: ["Pickup Boy"],
        summary: "Update current GPS coordinates and vehicle number",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "latitude", type: "number", example: 19.0760),
                    new OA\Property(property: "longitude", type: "number", example: 72.8777),
                    new OA\Property(property: "vehicle_number", type: "string", example: "GJ-01-AB-1234")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Location Updated")
        ]
    )]
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'vehicle_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $request->user();
        $updates = $request->only(['latitude', 'longitude', 'vehicle_number']);
        $updates['location_updated_at'] = now();
        $updates['last_active_at'] = now();
        $user->update($updates);

        // Log to history
        PickupBoyLocation::create([
            'pickup_boy_id' => $user->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return $this->successResponse('profile.location_updated', [
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
            'vehicle_number' => $user->vehicle_number,
            'location_updated_at' => $user->location_updated_at->format('Y-m-d H:i:s'),
            'last_active_at' => $user->last_active_at->format('Y-m-d H:i:s'),
        ]);
    }

    #[OA\Get(
        path: "/api/pickup-boy/dashboard",
        operationId: "getPickupBoyDashboard",
        tags: ["Pickup Boy"],
        summary: "Get dashboard counts and upcoming routes",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Dashboard fetched")
        ]
    )]
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $period = strtolower((string) $request->query('period', 'month'));

        $assignments = \App\Models\Assignment::where('pickup_boy_id', $user->id)
            ->whereHas('pickupRequest', function ($q) use ($period) {
                $this->applyPeriodFilter($q, 'scheduled_at', $period);
            })
            ->with(['pickupRequest.customer', 'pickupRequest.items.category'])
            ->get();

        $activeAssignments = $assignments->filter(function ($a) {
            return !in_array($a->status, ['completed', 'rejected', 'cancelled']);
        })->sortBy(function ($a) {
            return $a->pickupRequest->scheduled_at;
        });

        $completedCountQuery = \App\Models\Assignment::where('pickup_boy_id', $user->id)
            ->where('status', 'completed');
        $this->applyPeriodFilter($completedCountQuery, 'completed_at', $period);
        $completedCount = $completedCountQuery->count();

        $pendingCount = $activeAssignments->count();

        $allAssignmentsQuery = \App\Models\Assignment::where('pickup_boy_id', $user->id);
        $totalPickupsQuery = (clone $allAssignmentsQuery)->whereNotIn('status', ['rejected', 'cancelled']);
        $this->applyPeriodFilter($totalPickupsQuery, 'created_at', $period);
        $totalPickups = $totalPickupsQuery->count();

        $assignedCountQuery = (clone $allAssignmentsQuery)->whereIn('status', ['assigned', 'accepted', 'on_the_way', 'arrived', 'verifying', 'picked_up']);
        $this->applyPeriodFilter($assignedCountQuery, 'assigned_at', $period);
        $assignedCount = $assignedCountQuery->count();

        $rejectedCountQuery = (clone $allAssignmentsQuery)->whereIn('status', ['rejected', 'cancelled']);
        $this->applyPeriodFilter($rejectedCountQuery, 'updated_at', $period);
        $rejectedCount = $rejectedCountQuery->count();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $completedThisMonth = \App\Models\Assignment::where('pickup_boy_id', $user->id)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$monthStart, $monthEnd])
            ->count();
        $completedToday = \App\Models\Assignment::where('pickup_boy_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', now()->toDateString())
            ->count();
        $completedOverall = \App\Models\Assignment::where('pickup_boy_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $currentTaskAssignment = $activeAssignments->first();
        $upcomingRoutes = $activeAssignments->slice(1)->take(5)->map(function ($a) use ($user) {
            return [
                'pickup_id' => $a->pickup_request_id,
                'order_code' => $a->pickupRequest->pickup_code,
                'customer_name' => $a->pickupRequest->customer_name ?: ($a->pickupRequest->customer ? $a->pickupRequest->customer->name : null),
                'customer_phone' => $a->pickupRequest->customer_phone ?: ($a->pickupRequest->customer ? $a->pickupRequest->customer->phone : null),
                'customer_image' => $a->pickupRequest->customer ? $a->pickupRequest->customer->profile_photo_path : null,
                'address' => $a->pickupRequest->address ?: ($a->pickupRequest->address()->first() ? $a->pickupRequest->address()->first()->address_line_1 : null),
                'latitude' => $a->pickupRequest->latitude,
                'longitude' => $a->pickupRequest->longitude,
                'distance_km' => ($user->latitude && $user->longitude && $a->pickupRequest->latitude && $a->pickupRequest->longitude) ? round($this->calculateDistance($user->latitude, $user->longitude, $a->pickupRequest->latitude, $a->pickupRequest->longitude), 2) : 0,
                'scheduled_at' => $a->pickupRequest->scheduled_at ? $a->pickupRequest->scheduled_at->format('Y-m-d H:i:s') : null,
                'items_summary' => $a->pickupRequest->items->map(fn($i) => $i->category ? $i->category->name : 'Item')->implode(', '),
                'estimated_weight_kg' => $a->pickupRequest->items->sum('weight'),
                'status' => $a->status,
            ];
        })->values();

        return $this->successResponse('dashboard.fetched', [
            'pickup_boy' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'profile_image' => $user->profile_photo_path,
                'profile_photo_url' => $user->profile_photo_url,
                'employee_id' => $user->ensureEmployeeId(),
                'is_online' => (bool) $user->is_online,
                'is_available' => (bool) $user->is_available
            ],
            'summary' => [
                'period' => $period,
                'pending_count' => $pendingCount,
                'completed_count' => $completedCount,
                'total_pickups' => $totalPickups,
                'assigned_count' => $assignedCount,
                'rejected_count' => $rejectedCount,
                'today' => [
                    'completed_count' => $completedToday,
                ],
                'month' => [
                    'completed_count' => $completedThisMonth,
                ],
                'overall' => [
                    'completed_count' => $completedOverall,
                ],
            ],
            'current_task' => $currentTaskAssignment ? [
                'pickup_id' => $currentTaskAssignment->pickup_request_id,
                'order_code' => $currentTaskAssignment->pickupRequest->pickup_code,
                'customer_name' => $currentTaskAssignment->pickupRequest->customer_name ?: ($currentTaskAssignment->pickupRequest->customer ? $currentTaskAssignment->pickupRequest->customer->name : null),
                'customer_phone' => $currentTaskAssignment->pickupRequest->customer_phone ?: ($currentTaskAssignment->pickupRequest->customer ? $currentTaskAssignment->pickupRequest->customer->phone : null),
                'customer_image' => $currentTaskAssignment->pickupRequest->customer ? $currentTaskAssignment->pickupRequest->customer->profile_photo_path : null,
                'address' => $currentTaskAssignment->pickupRequest->address ?: ($currentTaskAssignment->pickupRequest->address()->first() ? $currentTaskAssignment->pickupRequest->address()->first()->address_line_1 : null),
                'latitude' => $currentTaskAssignment->pickupRequest->latitude,
                'longitude' => $currentTaskAssignment->pickupRequest->longitude,
                'distance_km' => ($user->latitude && $user->longitude && $currentTaskAssignment->pickupRequest->latitude && $currentTaskAssignment->pickupRequest->longitude) ? round($this->calculateDistance($user->latitude, $user->longitude, $currentTaskAssignment->pickupRequest->latitude, $currentTaskAssignment->pickupRequest->longitude), 2) : 0,
                'scheduled_at' => $currentTaskAssignment->pickupRequest->scheduled_at ? $currentTaskAssignment->pickupRequest->scheduled_at->format('Y-m-d H:i:s') : null,
                'items_summary' => $currentTaskAssignment->pickupRequest->items->map(fn($i) => $i->category ? $i->category->name : 'Item')->implode(', '),
                'estimated_weight_kg' => $currentTaskAssignment->pickupRequest->items->sum('weight'),
                'status' => $currentTaskAssignment->status,
            ] : null,
            'upcoming_route' => $upcomingRoutes
        ]);
    }

    private function applyPeriodFilter($query, string $column, string $period): void
    {
        if ($period === 'today') {
            $query->whereDate($column, now()->toDateString());
            return;
        }

        if ($period === 'month') {
            $query->whereBetween($column, [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ]);
            return;
        }

        // overall => no date filter
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        // Ensure $a is within [0, 1] to avoid square root of negative due to float precision
        $a = max(0, min(1, $a));
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getRescheduleSlots(Request $request, $id)
    {
        $locationController = new \App\Http\Controllers\Api\LocationController();
        return $locationController->pickupSlots($request);
    }

    public function arrivePickup(Request $request, $id)
    {
        $request->merge(['status' => 'arrived']);
        return $this->updateTravelStatus($request, $id);
    }

    public function cancelPickup(Request $request, $id)
    {
        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->firstOrFail();

        if ($assignment->status === 'completed' || $assignment->status === 'cancelled') {
            return $this->errorResponse('pickup.invalid_status_transition', 400);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($assignment, $request) {
            $assignment->update(['status' => 'cancelled']);
            $assignment->pickupRequest->update(['status' => 'cancelled']);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $assignment->pickup_request_id,
                'status' => 'cancelled',
                'notes' => 'Agent cancelled pickup at site.',
                'created_by' => $request->user()->id
            ]);
        });

        return $this->successResponse('pickup.cancelled_success');
    }

    #[OA\Post(
        path: "/api/pickup-boy/pickups/{id}/reschedule-request",
        operationId: "requestReschedule",
        tags: ["Pickup Boy"],
        summary: "Request a reschedule with reason",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Reschedule request submitted"),
            new OA\Response(response: 404, description: "Assignment not found")
        ]
    )]
    public function requestReschedule(Request $request, $id)
    {
        $request->validate([
            'reason_code' => 'required|string',
            'reason_text' => 'nullable|string',
            'additional_note' => 'nullable|string'
        ]);

        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->firstOrFail();

        \Illuminate\Support\Facades\DB::transaction(function () use ($assignment, $request) {
            $assignment->update(['status' => 'reschedule_requested']);
            $assignment->pickupRequest->update([
                'status' => 'reschedule_requested',
                'reschedule_reason' => $request->reason_code . ': ' . ($request->reason_text ?? '') . ' - ' . ($request->additional_note ?? '')
            ]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $assignment->pickup_request_id,
                'status' => 'reschedule_requested',
                'notes' => 'Agent requested reschedule: ' . $request->reason_code,
                'created_by' => $request->user()->id
            ]);

        });

        return $this->successResponse('pickup.reschedule_requested');
    }

    /**
     * Donation pickup verification — photo + optional remarks + optional items.
     * No price modification, no payment, no final_amount.
     */
    protected function verifyDonation(Request $request, PickupRequest $pickup, Assignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'remarks' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable|string',
        ]);
        if ($validator->fails())
            return $this->validationErrorResponse($validator->errors());

        try {
            DB::beginTransaction();

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('pickup_images', 'public');
                    PickupImage::create([
                        'pickup_request_id' => $pickup->id,
                        'image_path' => $path,
                        'type' => 'verification',
                    ]);
                }
            }

            foreach ($request->input('items', []) as $item) {
                PickupItem::create([
                    'pickup_request_id' => $pickup->id,
                    'category_id' => null,
                    'product_name' => $item['product_name'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'weight' => $item['weight'] ?? 0,
                    'price_per_unit' => 0,
                    'total_price' => 0,
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            $pickup->update([
                'status' => 'completed',
                'price_locked_at' => now(),
            ]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'completed',
                'notes' => $request->remarks ?? 'Donation pickup completed.',
                'created_by' => $request->user()->id,
            ]);

            $assignment->update(['status' => 'completed', 'completed_at' => now()]);

            DB::commit();
            return $this->successResponse('pickup.donation_completed', $pickup->load('items', 'images'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Update final payable amount before completion (writes price log).
     */
    public function updateFinalPrice(Request $request, $id, PickupPriceService $priceService)
    {
        $pickup = PickupRequest::findOrFail($id);

        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->where('status', '!=', 'completed')
            ->first();
        if (!$assignment)
            return $this->errorResponse('auth.unauthorized', 403);

        $validator = Validator::make($request->all(), [
            'final_amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);
        if ($validator->fails())
            return $this->validationErrorResponse($validator->errors());

        $result = $priceService->modify($pickup, (float) $request->final_amount, $request->user(), 'pickup_boy', $request->reason);
        if (!$result['ok'])
            return $this->errorResponse($result['message'], 422);

        return $this->successResponse($result['message'], $result['pickup']);
    }

    /**
     * Add ad-hoc product item at pickup time.
     */
    public function addPickupItem(Request $request, $id)
    {
        $pickup = PickupRequest::findOrFail($id);

        if ($pickup->isPriceLocked()) {
            return $this->errorResponse('pickup.price_locked', 422);
        }

        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->where('status', '!=', 'completed')
            ->first();
        if (!$assignment)
            return $this->errorResponse('auth.unauthorized', 403);

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'product_name' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'weight' => 'nullable|numeric|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);
        if ($validator->fails())
            return $this->validationErrorResponse($validator->errors());

        $rate = (float) ($request->price_per_unit ?? 0);
        $weight = (float) ($request->weight ?? 0);
        $qty = (int) ($request->quantity ?? 1);
        $total = $weight > 0 ? $rate * $weight : $rate * $qty;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('pickup_items', 'public');
        }

        $item = PickupItem::create([
            'pickup_request_id' => $pickup->id,
            'category_id' => $request->category_id,
            'product_name' => $request->product_name,
            'quantity' => $qty,
            'weight' => $weight,
            'price_per_unit' => $rate,
            'total_price' => $total,
            'image_path' => $imagePath,
            'remarks' => $request->remarks,
        ]);

        return $this->successResponse('pickup.item_added', $item);
    }

    /**
     * Update assignment status (reached_location, pickup_started).
     */
    public function updateAssignmentStatus(Request $request, $id, PickupAssignmentService $service)
    {
        $assignment = Assignment::where('pickup_request_id', $id)
            ->where('pickup_boy_id', $request->user()->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->first();
        if (!$assignment)
            return $this->errorResponse('auth.unauthorized', 403);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,reached_location,pickup_started,cancelled',
            'remarks' => 'nullable|string',
        ]);
        if ($validator->fails())
            return $this->validationErrorResponse($validator->errors());

        $assignment = $service->updateStatus($assignment, $request->status, $request->remarks);
        return $this->successResponse('pickup.status_updated', $assignment);
    }

    /**
     * Update final amount (pickup boy edits amount if items differ from estimate)
     * Only for corporate bookings
     */
    public function updateFinalAmount(Request $request, $id)
    {
        $pickupRequest = PickupRequest::findOrFail($id);
        $user = Auth::user();

        // Only pickup boys can edit amounts
        if (!$user->hasRole('pickup_boy')) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        // Only corporate bookings allow amount editing
        if ($pickupRequest->request_type !== 'corporate') {
            return $this->errorResponse('pickup.amount_edit_not_allowed', 400);
        }

        // Check pickup boy is assigned to this request
        $assignment = Assignment::where('pickup_request_id', $pickupRequest->id)
            ->where('pickup_boy_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->first();

        if (!$assignment) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $oldAmount = $pickupRequest->final_amount;

            // Get estimate if it exists
            $estimate = $pickupRequest->latestEstimate;
            if ($estimate && $validated['amount'] > $estimate->estimated_amount * 1.5) {
                // Allow up to 50% variance, but warn
                return $this->errorResponse('pickup.amount_exceeds_estimate', 400,
                    "Amount {$validated['amount']} exceeds estimate {$estimate->estimated_amount} by more than 50%");
            }

            // Update final amount
            $pickupRequest->update([
                'final_amount' => $validated['amount'],
                'metadata' => array_merge(
                    is_array($pickupRequest->metadata) ? $pickupRequest->metadata : json_decode($pickupRequest->metadata, true) ?? [],
                    [
                        'amount_edited_by_pickup_boy' => true,
                        'original_amount' => $oldAmount,
                        'edited_amount' => $validated['amount'],
                        'edit_reason' => $validated['reason'] ?? null,
                        'edited_at' => now()->toIso8601String(),
                    ]
                ),
            ]);

            // Log status change (not a real status change, just audit)
            RequestStatusLog::logStatusChange(
                $pickupRequest->id,
                $pickupRequest->status_new,
                $pickupRequest->status_new,
                $user->id,
                'pickup_boy',
                "Amount edited from {$oldAmount} to {$validated['amount']}: " . ($validated['reason'] ?? 'Items differ from estimate')
            );

            return $this->successResponse('pickup.amount_updated', [
                'request_id' => $pickupRequest->id,
                'old_amount' => $oldAmount,
                'new_amount' => $validated['amount'],
                'estimate_amount' => $estimate?->estimated_amount,
                'edited_at' => now(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('pickup.amount_update_failed', 400, $e->getMessage());
        }
    }
}
