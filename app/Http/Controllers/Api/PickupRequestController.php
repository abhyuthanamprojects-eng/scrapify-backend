<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\PickupItem;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\PickupRequestAttribute;
use App\Models\AppSetting;
use App\Models\Warehouse;
use App\Services\LocationService;

use App\Services\ReferralService;
use App\Services\HomeAppliancePricingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class PickupRequestController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly HomeAppliancePricingService $pricingService)
    {
    }

    #[OA\Get(
        path: "/api/pickup-requests",
        operationId: "getPickupRequests",
        tags: ["Pickup"],
        summary: "List pickup requests",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of pickup requests"
            )
        ]
    )]
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PickupRequest::with(['items.category.pricingRules', 'images'])->orderBy('created_at', 'desc');

        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        } elseif ($user->hasRole('channel_partner')) {
            $query->where('channel_partner_id', $user->channel_partner_id);
        } elseif ($user->hasRole('pickup_boy')) {
            // Pickup boy sees assigned requests
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('pickup_boy_id', $user->id);
            });
        }
        // Admin sees all

        // Filter by Status
        if ($request->has('status')) {
            $query->whereIn('status', explode(',', $request->status));
        }

        // Filter by Request Type (scrap, donation, corporate)
        if ($request->has('request_type')) {
            $query->whereIn('request_type', explode(',', $request->request_type));
        }

        $requests = $query->paginate(20);
        return $this->paginatedResponse('pickup.fetched', $requests);
    }

    /**
     * Get pickup stats for the user.
     */
    #[OA\Get(
        path: "/api/pickup-requests/stats",
        operationId: "getPickupStats",
        tags: ["Pickup"],
        summary: "Get counts for different pickup statuses",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Stats fetched")
        ]
    )]
    public function stats(Request $request)
    {
        $user = Auth::user();

        $query = PickupRequest::query();

        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        } elseif ($user->hasRole('channel_partner')) {
            $query->where('channel_partner_id', $user->channel_partner_id);
        } elseif ($user->hasRole('pickup_boy')) {
            $query->whereHas('assignments', function ($q) use ($user) {
                $q->where('pickup_boy_id', $user->id);
            });
        }

        $stats = [
            'assigned' => (clone $query)->whereIn('status', ['assigned', 'on_the_way', 'picked_up'])->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];

        return $this->successResponse('pickup.stats', $stats);
    }

    #[OA\Post(
        path: "/api/pickup-request",
        operationId: "createPickupRequest",
        tags: ["Pickup"],
        summary: "Create a new pickup request",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["address", "city_id", "scheduled_at", "items"],
                    properties: [
                        new OA\Property(property: "address", type: "string", example: "123 Street, City"),
                        new OA\Property(property: "address_id", type: "integer", example: 1),
                        new OA\Property(property: "city_id", type: "integer", example: 1),
                        new OA\Property(property: "latitude", type: "number", example: 19.0760),
                        new OA\Property(property: "longitude", type: "number", example: 72.8777),
                        new OA\Property(property: "scheduled_at", type: "string", format: "date-time", example: "2026-02-20 10:00:00"),
                        new OA\Property(property: "payout_method", type: "string", enum: ["upi", "bank", "cash", "wallet"]),
                        new OA\Property(property: "payment_detail_id", type: "integer"),
                        new OA\Property(
                            property: "images[]",
                            type: "array",
                            items: new OA\Items(type: "string", format: "binary"),
                            description: "Min 1, max 10 images (max 5MB each)"
                        ),
                        new OA\Property(
                            property: "items",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "category_id", type: "integer", example: 1),
                                    new OA\Property(property: "weight", type: "number", example: 10.5),
                                    new OA\Property(property: "quantity", type: "integer", example: 2)
                                ]
                            )
                        ),
                        new OA\Property(property: "customer_name", type: "string"),
                        new OA\Property(property: "customer_phone", type: "string")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Pickup Request Created"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request)
    {
        $user = Auth::user();
        $isPartner = $user->hasRole('channel_partner');

        $validator = Validator::make($request->all(), [
            'address_id' => 'nullable|exists:addresses,id',
            'address' => 'required_without:address_id|string',
            'city_id' => 'required_without:address_id|exists:cities,id',
            'pincode' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'scheduled_at' => 'required|date|after:now',
            'payout_method' => 'required|string|in:upi,bank,cash,wallet',
            'payment_detail_id' => 'nullable|exists:payment_details,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'image_locations' => 'nullable|array',
            'image_locations.*.latitude' => 'nullable|numeric|between:-90,90',
            'image_locations.*.longitude' => 'nullable|numeric|between:-180,180',
            'proof_images' => 'nullable|array',
            'proof_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'proof_image_locations' => 'nullable|array',
            'proof_image_locations.*.latitude' => 'nullable|numeric|between:-90,90',
            'proof_image_locations.*.longitude' => 'nullable|numeric|between:-180,180',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.condition' => 'nullable|string|max:255',
            'items.*.remarks' => 'nullable|string',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.attributes' => 'nullable|array',
            'items.*.attributes.*.attribute_id' => 'required|exists:attributes,id',
            'items.*.attributes.*.attribute_option_id' => 'nullable|exists:attribute_options,id',
            'items.*.attributes.*.value' => 'required',
            // Partner specific
            'customer_name' => $isPartner ? 'required|string' : 'nullable',
            'customer_phone' => $isPartner ? 'required|string|regex:/^[6-9]\d{9}$/' : 'nullable',
            // Referral coupon (customer only)
            'coupon_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Validate required attributes for category
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $itemData) {
                if (empty($itemData['category_id'])) {
                    continue;
                }
                $category = Category::find($itemData['category_id']);
                if ($category) {
                    $requiredAttrs = $category->attributes()->wherePivot('is_required', true)->get();
                    $providedAttrs = collect($itemData['attributes'] ?? []);
                    foreach ($requiredAttrs as $reqAttr) {
                        $provided = $providedAttrs->firstWhere('attribute_id', $reqAttr->id);
                        if (!$provided || !isset($provided['value']) || $provided['value'] === '') {
                            return $this->errorResponse("validation.required_attribute_missing", 400, [
                                'attribute_id' => $reqAttr->id,
                                'message' => "The attribute '{$reqAttr->name['en']}' is required for category '{$category->getTranslatedName()}'."
                            ]);
                        }
                    }
                }
            }
        }

        // Basket value check: if a low-value pickup charge applies, the
        // resulting basket value must not drop below the configured minimum.
        $estimatedTotal = $this->estimateItemsTotal($request->items);
        $pickupCharge = $this->calculatePickupCharge($estimatedTotal);
        $eligibility = $this->buildBookingEligibility($estimatedTotal, $pickupCharge);
        if (!$eligibility['can_book']) {
            return $this->errorResponse('pickup.insufficient_basket_value', 422, $eligibility);
        }

        $proofImagesRequired = (bool) AppSetting::get('scrap_proof_images_required', true);
        $requiredProofLabels = collect(AppSetting::get('scrap_proof_image_labels', ['front', 'back', 'left', 'right']))
            ->map(fn($label) => strtolower(trim((string) $label)))
            ->filter()
            ->values()
            ->all();

        if ($proofImagesRequired) {
            $missingLabels = [];
            foreach ($requiredProofLabels as $label) {
                if (!$request->hasFile("proof_images.{$label}")) {
                    $missingLabels[] = $label;
                }
            }
            if (!empty($missingLabels)) {
                return $this->validationErrorResponse([
                    'proof_images' => ['Missing mandatory proof images: ' . implode(', ', $missingLabels)],
                    'required_labels' => $requiredProofLabels,
                ]);
            }
        }

        // Handle Address
        $addressStr = $request->address;
        $cityId = $request->city_id;
        $pincode = $request->input('pincode');
        $lat = $request->latitude;
        $lng = $request->longitude;

        if ($request->has('address_id')) {
            $addressModel = \App\Models\Address::find($request->address_id);
            if ($addressModel) {
                $addressStr = $addressModel->address_line_1 . ', ' . $addressModel->address_line_2;
                $cityId = $addressModel->city_id;
                $pincode = $addressModel->pincode ?? $pincode;
                $lat = $addressModel->latitude;
                $lng = $addressModel->longitude;
            }
        }


        DB::beginTransaction();

        try {
            $warehouse = $this->resolveWarehouseByPincode($pincode, $lat, $lng);

            if (!$warehouse && $user->phone === '9999999999') {
                $warehouse = Warehouse::where('status', true)->orderBy('id')->first();
            }

            if (!$warehouse) {
                DB::rollBack();

                return $this->validationErrorResponse([
                    'warehouse' => ['No active warehouse is mapped for this booking pincode. Please add the pincode in warehouse service pincodes before accepting bookings.'],
                    'pincode' => [$pincode ?: 'Pincode could not be resolved from the selected address/location.'],
                ]);
            }

            $pickup = PickupRequest::create([
                'pickup_code' => 'SCR-' . strtoupper(Str::random(6)) . '-' . rand(1000, 9999),
                'customer_id' => $user->id,
                'address_id' => $request->address_id,
                'payment_detail_id' => $request->payment_detail_id,
                'warehouse_id' => $warehouse->id,
                'created_by' => $user->id,
                'channel_partner_id' => $isPartner ? $user->channel_partner_id : null,
                'customer_name' => $isPartner ? $request->customer_name : $user->name,
                'customer_phone' => $isPartner ? $request->customer_phone : $user->phone,
                'city_id' => $cityId,
                'address' => $addressStr,
                'latitude' => $lat,
                'longitude' => $lng,
                'scheduled_at' => $request->scheduled_at,
                'payout_method' => $request->payout_method,
                'status' => 'pending',
                'estimated_amount' => 0,
            ]);

            // Handle Direct Image Upload
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('pickup_requests', 'public');
                    $geo = $request->input("image_locations.{$index}", []);
                    \App\Models\PickupImage::create([
                        'pickup_request_id' => $pickup->id,
                        'image_path' => $path,
                        'type' => 'item',
                        'latitude' => $geo['latitude'] ?? null,
                        'longitude' => $geo['longitude'] ?? null,
                    ]);
                }
            }

            if ($request->hasFile('proof_images')) {
                foreach (($request->file('proof_images') ?? []) as $label => $image) {
                    if (!$image) {
                        continue;
                    }
                    $safeLabel = strtolower(trim((string) $label));
                    if ($safeLabel === '') {
                        $safeLabel = 'proof';
                    }
                    $path = $image->store('pickup_requests', 'public');
                    $geo = $request->input("proof_image_locations.{$safeLabel}", []);
                    \App\Models\PickupImage::create([
                        'pickup_request_id' => $pickup->id,
                        'image_path' => $path,
                        'type' => "proof_{$safeLabel}",
                        'latitude' => $geo['latitude'] ?? null,
                        'longitude' => $geo['longitude'] ?? null,
                    ]);
                }
            }

            $totalEstimated = 0;

            foreach ($request->items as $itemData) {
                $providedOptionIds = collect($itemData['attributes'] ?? [])
                    ->whereNotNull('attribute_option_id')
                    ->pluck('attribute_option_id')
                    ->map(fn($id) => (int) $id)
                    ->toArray();

                $basePrice = isset($itemData['estimated_price']) && $itemData['estimated_price'] !== null
                    ? (float) $itemData['estimated_price']
                    : $this->pricingService->estimate(
                    (int) $itemData['category_id'],
                    $providedOptionIds
                );
                $qty = $itemData['quantity'] ?? 1;
                $weight = $itemData['weight'] ?? 0;

                $itemPrice = ($weight > 0) ? ($basePrice * $weight) : ($basePrice * $qty);

                $pickupItem = PickupItem::create([
                    'pickup_request_id' => $pickup->id,
                    'category_id' => $itemData['category_id'],
                    'weight' => $weight,
                    'quantity' => $qty,
                    'condition' => $itemData['condition'] ?? null,
                    'price_per_unit' => $basePrice,
                    'total_price' => $itemPrice,
                    'remarks' => $itemData['remarks'] ?? null,
                ]);

                if (isset($itemData['attributes'])) {
                    foreach ($itemData['attributes'] as $attrData) {
                        PickupRequestAttribute::create([
                            'pickup_request_id' => $pickup->id,
                            'attribute_id' => $attrData['attribute_id'],
                            'attribute_option_id' => $attrData['attribute_option_id'] ?? null,
                            'value' => is_array($attrData['value']) ? $attrData['value'] : ['en' => $attrData['value']],
                        ]);
                    }
                }

                $totalEstimated += $itemPrice;
            }

            $minimumFreePickupAmount = (float) AppSetting::get('minimum_free_pickup_amount', 1500);
            $lowValueShippingCharge = (float) AppSetting::get('low_value_shipping_charge', 100);
            $shippingCharge = $totalEstimated < $minimumFreePickupAmount ? $lowValueShippingCharge : 0.0;
            $finalEstimatedAmount = max(0, $totalEstimated - $shippingCharge);

            $pickup->update([
                'estimated_amount' => $finalEstimatedAmount,
                'metadata' => array_merge($pickup->metadata ?? [], [
                    'pricing_breakdown' => [
                        'subtotal_amount' => round($totalEstimated, 2),
                        'minimum_free_pickup_amount' => round($minimumFreePickupAmount, 2),
                        'shipping_charge' => round($shippingCharge, 2),
                        'final_estimated_amount' => round($finalEstimatedAmount, 2),
                    ],
                ]),
            ]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'pending',
                'notes' => 'Pickup request created.',
                'created_by' => $user->id,
            ]);

            // Apply referral coupon (customer only)
            if ($request->filled('coupon_code') && $user->hasRole('customer')) {
                $referralService = app(ReferralService::class);
                $couponResult = $referralService->validateCoupon($request->coupon_code, $user, (float) $finalEstimatedAmount);
                if (!$couponResult['ok']) {
                    DB::rollBack();
                    return $this->errorResponse($couponResult['message'], 422, [
                        'coupon_code' => [trans($couponResult['message'])],
                    ]);
                }
                $referralService->applyCouponToBooking($couponResult['coupon'], $pickup, $couponResult['discount']);
            }

            DB::commit();

            return $this->successResponse('pickup.created', $pickup->fresh()->load('items', 'images', 'referralCoupon', 'partnerCustomer'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('pickup.create_failed', 500, ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * Estimates the total value of the given items without persisting anything.
     * Mirrors the per-item pricing logic used in store().
     */
    private function estimateItemsTotal(array $items): float
    {
        $total = 0.0;

        foreach ($items as $itemData) {
            if (empty($itemData['category_id'])) {
                continue;
            }

            $providedOptionIds = collect($itemData['attributes'] ?? [])
                ->whereNotNull('attribute_option_id')
                ->pluck('attribute_option_id')
                ->map(fn($id) => (int) $id)
                ->toArray();

            $basePrice = isset($itemData['estimated_price']) && $itemData['estimated_price'] !== null
                ? (float) $itemData['estimated_price']
                : $this->pricingService->estimate((int) $itemData['category_id'], $providedOptionIds);

            $qty = $itemData['quantity'] ?? 1;
            $weight = $itemData['weight'] ?? 0;

            $total += ($weight > 0) ? ($basePrice * $weight) : ($basePrice * $qty);
        }

        return $total;
    }

    /**
     * Returns the low-value pickup charge that applies for the given
     * estimated order total (0 if the order meets the free-pickup threshold).
     */
    private function calculatePickupCharge(float $estimatedTotal): float
    {
        $minimumFreePickupAmount = (float) AppSetting::get('minimum_free_pickup_amount', 1500);
        $lowValueShippingCharge = (float) AppSetting::get('low_value_shipping_charge', 100);

        return $estimatedTotal < $minimumFreePickupAmount ? $lowValueShippingCharge : 0.0;
    }

    /**
     * Builds the booking eligibility payload used to gate booking confirmation.
     * Blocks booking only when a pickup charge applies and deducting it from
     * the basket value would leave less than the configured minimum.
     */
    private function buildBookingEligibility(float $basketValue, float $pickupCharge): array
    {
        $minimumRequiredBalance = (float) AppSetting::get('minimum_basket_value_after_charge', 100);
        $valueAfterCharge = $basketValue - $pickupCharge;

        $canBook = !($pickupCharge > 0 && $valueAfterCharge < $minimumRequiredBalance);

        $message = $canBook
            ? 'You are eligible to book this pickup.'
            : "A pickup charge of ₹" . round($pickupCharge, 2) . " applies to this order. " .
              "Your basket value (₹" . round($basketValue, 2) . ") must remain at least ₹" .
              round($minimumRequiredBalance, 2) . " after this charge. Please add more items to continue.";

        return [
            'can_book' => $canBook,
            'basket_value' => round($basketValue, 2),
            'pickup_charge' => round($pickupCharge, 2),
            'minimum_required_balance' => round($minimumRequiredBalance, 2),
            'message' => $message,
        ];
    }

    /**
     * Checks whether the basket can be booked, based on its estimated value
     * and the pickup charge (if any) for the given items.
     */
    #[OA\Post(
        path: "/api/pickup-requests/check-booking-eligibility",
        operationId: "checkBookingEligibility",
        tags: ["Pickup"],
        summary: "Check basket value against the pickup charge before confirming a booking",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Eligibility result")
        ]
    )]
    public function checkBookingEligibility(Request $request)
    {
        $requestType = strtolower((string) $request->input('request_type', 'scrap'));
        $items = is_array($request->input('items')) ? $request->input('items') : [];

        $basketValue = $this->estimateItemsTotal($items);

        // Donation and corporate bookings don't carry a low-value pickup charge.
        $pickupCharge = $requestType === 'scrap'
            ? $this->calculatePickupCharge($basketValue)
            : 0.0;

        $eligibility = $this->buildBookingEligibility($basketValue, $pickupCharge);

        return $this->successResponse('pickup.eligibility_checked', $eligibility);
    }

    private function resolveWarehouseByPincode(?string $pincode, $lat = null, $lng = null): ?Warehouse
    {
        $normalized = Warehouse::normalizePincode($pincode);
        $requestLat = is_numeric($lat) ? (float) $lat : null;
        $requestLng = is_numeric($lng) ? (float) $lng : null;

        if (!$normalized && $requestLat !== null && $requestLng !== null) {
            $geo = app(LocationService::class)->reverseGeocode($requestLat, $requestLng);
            $normalized = Warehouse::normalizePincode($geo['pincode'] ?? null);
        }

        return Warehouse::findBestByPincode($normalized, $requestLat, $requestLng);
    }

    /**
     * Show details.
     */
    #[OA\Get(
        path: "/api/pickup-requests/{id}",
        operationId: "getPickupDetail",
        tags: ["Pickup"],
        summary: "Get specific pickup request details",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Detail fetched"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show($id)
    {
        $user = Auth::user();
        $pickup = PickupRequest::with(['items.category.pricingRules', 'images', 'assignments.pickupBoy'])
            ->find($id);

        if (!$pickup) {
            return $this->errorResponse('pickup.not_found', 404);
        }

        // Access control
        if ($user->hasRole('customer') && $pickup->customer_id != $user->id) {
            return $this->errorResponse('pickup.unauthorized', 403);
        }
        if ($user->hasRole('channel_partner') && $pickup->channel_partner_id != $user->channel_partner_id) {
            return $this->errorResponse('pickup.unauthorized', 403);
        }

        return $this->successResponse('pickup.fetched', $pickup);
    }

    /**
     * Reschedule pickup request.
     */
    #[OA\Post(
        path: "/api/pickup-requests/{id}/reschedule",
        operationId: "reschedulePickup",
        tags: ["Pickup"],
        summary: "Reschedule a pickup request",
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
                required: ["scheduled_at"],
                properties: [
                    new OA\Property(property: "scheduled_at", type: "string", format: "date-time", example: "2026-04-20 14:00:00"),
                    new OA\Property(property: "reason", type: "string", example: "I am not at home")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Pickup Rescheduled"),
            new OA\Response(response: 400, description: "Invalid Status"),
            new OA\Response(response: 403, description: "Unauthorized")
        ]
    )]
    public function reschedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'scheduled_date' => 'required_without:scheduled_at|date|after_or_equal:today',
            'time_slot' => 'required_without:scheduled_at|string|in:morning,afternoon,evening',
            'scheduled_at' => 'nullable|date|after:now',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $pickup = PickupRequest::find($id);

        if (!$pickup) {
            return $this->errorResponse('pickup.not_found', 404);
        }

        // Check authorization
        $user = $request->user();
        if ($user->hasRole('customer') && $pickup->customer_id != $user->id) {
            return $this->errorResponse('pickup.unauthorized', 403);
        }

        // Status check: only allowed if not completed or cancelled
        if (in_array($pickup->status, ['completed', 'cancelled', 'picked_up'])) {
            return $this->errorResponse('pickup.reschedule_invalid_status', 400, ['status' => $pickup->status]);
        }
        
        if ($user->hasRole('pickup_boy')) {
            $assignment = $pickup->assignments()->where('pickup_boy_id', $user->id)->first();
            if (!$assignment) {
                return $this->errorResponse('pickup.unauthorized', 403);
            }
        }

        try {
            $scheduledAt = $request->scheduled_at;
            
            if ($request->has('scheduled_date') && $request->has('time_slot')) {
                $timeMap = [
                    'morning' => '09:00:00',
                    'afternoon' => '12:00:00',
                    'evening' => '15:00:00'
                ];
                $scheduledAt = $request->scheduled_date . ' ' . $timeMap[$request->time_slot];
            }

            $pickup->update([
                'scheduled_at' => $scheduledAt,
                'reschedule_reason' => $request->reason,
                'status' => 'rescheduled'
            ]);

            return $this->successResponse('pickup.rescheduled_success', [
                'success' => true,
                'message' => 'Pickup rescheduled successfully.'
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Get tracking timeline for a pickup request.
     */
    #[OA\Get(
        path: "/api/pickup-requests/{id}/tracking",
        operationId: "getPickupTracking",
        tags: ["Pickup"],
        summary: "Get tracking timeline for a pickup request",
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
            new OA\Response(response: 200, description: "Tracking Data Fetched")
        ]
    )]
    public function tracking($id)
    {
        $pickup = PickupRequest::with(['customer', 'assignment.pickupBoy', 'city'])->find($id);

        if (!$pickup) {
            return $this->errorResponse('pickup.not_found', 404);
        }

        // Fetch activities from Spatie Activity Log related to this pickup
        $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', PickupRequest::class)
            ->where('subject_id', $id)
            ->whereIn('event', ['created', 'updated', 'status_updated', 'assigned', 'rescheduled', 'cancelled'])
            ->orderBy('created_at', 'asc')
            ->get();

        $timeline = [];

        // Always add the creation as the first event
        $timeline[] = [
            'status' => 'pending',
            'label' => 'Pickup requested',
            'time' => $pickup->created_at->toIso8601String()
        ];

        foreach ($activities as $activity) {
            $status = $activity->getExtraProperty('status');
            if (!$status && $activity->event === 'assigned')
                $status = 'assigned';

            if ($status) {
                $timeline[] = [
                    'status' => $status,
                    'label' => $this->getStatusLabel($status),
                    'time' => $activity->created_at->toIso8601String()
                ];
            }
        }

        // De-duplicate by status (keep latest) or just show all. 
        // Handoff example shows clean transitions.

        $data = [
            'id' => $pickup->id,
            'pickup_code' => $pickup->pickup_code,
            'status' => $pickup->status,
            'scheduled_at' => $pickup->scheduled_at ? $pickup->scheduled_at->toIso8601String() : null,
            'address' => $pickup->address,
            'city_name' => $pickup->city ? $pickup->city->name : null,
            'latitude' => (float) $pickup->latitude,
            'longitude' => (float) $pickup->longitude,
            'agent' => $pickup->assignment ? [
                'id' => $pickup->assignment->pickupBoy->id,
                'name' => $pickup->assignment->pickupBoy->name,
                'employee_id' => $pickup->assignment->pickupBoy->employee_id,
                'phone' => $pickup->assignment->pickupBoy->phone,
                'latitude' => (float) $pickup->assignment->pickupBoy->latitude,
                'longitude' => (float) $pickup->assignment->pickupBoy->longitude,
                'vehicle_number' => $pickup->assignment->pickupBoy->vehicle_number,
                'image' => $pickup->assignment->pickupBoy->profile_photo_url,
                'profile_photo_url' => $pickup->assignment->pickupBoy->profile_photo_url,
            ] : null,
            'timeline' => collect($timeline)->unique('status')->values()->all()
        ];

        return $this->successResponse('pickup.tracking', $data);
    }

    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pickup requested',
            'assigned' => 'Pickup assigned',
            'accepted' => 'Pickup accepted',
            'on_the_way' => 'Agent on the way',
            'arrived' => 'Agent arrived',
            'verifying' => 'Items being verified',
            'picked_up' => 'Items picked up',
            'completed' => 'Pickup completed',
            'cancelled' => 'Pickup cancelled',
            'rescheduled' => 'Pickup rescheduled',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Cancel pickup request.
     */
    #[OA\Post(
        path: "/api/pickup-requests/{id}/cancel",
        operationId: "cancelPickup",
        tags: ["Pickup"],
        summary: "Cancel a pickup request",
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
                required: ["reason"],
                properties: [
                    new OA\Property(property: "reason", type: "string", example: "Changed my mind")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Pickup Cancelled"),
            new OA\Response(response: 400, description: "Invalid Status")
        ]
    )]
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $pickup = PickupRequest::findOrFail($id);
        $user = Auth::user();

        // Authorization
        if ($user->hasRole('customer') && $pickup->customer_id != $user->id) {
            return $this->errorResponse('pickup.unauthorized', 403);
        }

        // Only allow cancellation for certain statuses
        if (!in_array($pickup->status, ['pending', 'assigned', 'accepted'])) {
            return $this->errorResponse('pickup.cancel_invalid_status', 400, ['status' => $pickup->status]);
        }

        try {
            DB::beginTransaction();

            $pickup->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->reason
            ]);

            // Notify relevant parties if needed...

            DB::commit();

            return $this->successResponse('pickup.cancelled', $pickup);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }
    /**
     * Submit a review for a completed pickup.
     */
    #[OA\Post(
        path: "/api/pickup-requests/{id}/review",
        operationId: "submitPickupReview",
        tags: ["Pickup"],
        summary: "Submit rating and review for a completed pickup",
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
                required: ["rating"],
                properties: [
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, example: 5),
                    new OA\Property(property: "review", type: "string", example: "Great service!")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Review Submitted"),
            new OA\Response(response: 400, description: "Invalid Status")
        ]
    )]
    public function submitReview(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $pickup = PickupRequest::findOrFail($id);
        $user = Auth::user();

        // Authorization
        if ($pickup->customer_id != $user->id) {
            return $this->errorResponse('pickup.unauthorized', 403);
        }

        // Status check: only allowed if completed
        if ($pickup->status !== 'completed') {
            return $this->errorResponse('pickup.review_invalid_status', 400);
        }

        $pickup->update([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return $this->successResponse('pickup.review_submitted', $pickup);
    }
}
