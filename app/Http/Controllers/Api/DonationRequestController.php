<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\PickupItem;
use App\Models\PickupRequestAttribute;
use App\Models\AppSetting;
use App\Models\Warehouse;
use App\Services\LocationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class DonationRequestController extends Controller
{
    use ApiResponseTrait;

    public function products()
    {
        $products = collect(AppSetting::get('donation_products', ['Cloth', 'Shoes', 'Toys', 'Books']))
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        return $this->successResponse('donation.products_fetched', [
            'products' => $products,
        ]);
    }

    #[OA\Post(
        path: "/api/donation-request",
        operationId: "createDonationRequest",
        tags: ["Donation"],
        summary: "Create a new donation pickup request",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["address", "city_id", "scheduled_at", "donation_category", "items"],
                    properties: [
                        new OA\Property(property: "address", type: "string", example: "123 Street, City"),
                        new OA\Property(property: "address_id", type: "integer", example: 1),
                        new OA\Property(property: "city_id", type: "integer", example: 1),
                        new OA\Property(property: "latitude", type: "number", example: 19.0760),
                        new OA\Property(property: "longitude", type: "number", example: 72.8777),
                        new OA\Property(property: "scheduled_at", type: "string", format: "date-time", example: "2026-04-20 10:00:00"),
                        new OA\Property(property: "donation_category", type: "string", example: "clothes"),
                        new OA\Property(property: "notes", type: "string"),
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
                                    new OA\Property(property: "category_id", type: "integer", example: 21),
                                    new OA\Property(property: "weight", type: "number", example: 10.5),
                                    new OA\Property(property: "quantity", type: "integer", example: 2)
                                ]
                            )
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Donation Request Created"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request)
    {
        $user = Auth::user();
        $allowedDonationProducts = collect(AppSetting::get('donation_products', ['Cloth', 'Shoes', 'Toys', 'Books']))
            ->map(fn($item) => Str::lower(trim((string) $item)))
            ->filter()
            ->values()
            ->all();

        $validator = Validator::make($request->all(), [
            'address_id' => 'nullable|exists:addresses,id',
            'address' => 'required_without:address_id|string',
            'city_id' => 'required_without:address_id|exists:cities,id',
            'pincode' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'scheduled_at' => 'required|date|after:now',
            'donation_category' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.product_name' => 'required_without:items.*.category_id|string|max:255',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.attributes' => 'nullable|array',
            'items.*.attributes.*.attribute_id' => 'required|exists:attributes,id',
            'items.*.attributes.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $rawDonationCategory = Str::lower(trim((string) $request->donation_category));
        $selectedCategories = collect(explode(',', $rawDonationCategory))
            ->map(fn($item) => trim($item))
            ->filter()
            ->values()
            ->all();
        if (empty($selectedCategories)) {
            $selectedCategories = [$rawDonationCategory];
        }
        $invalidCategories = array_values(array_filter(
            $selectedCategories,
            fn($category) => !in_array($category, $allowedDonationProducts, true)
        ));
        if (!empty($invalidCategories)) {
            return $this->validationErrorResponse([
                'donation_category' => ['The selected donation category is invalid.'],
                'invalid_categories' => $invalidCategories,
                'allowed_donation_products' => $allowedDonationProducts,
            ]);
        }
        $donationCategory = implode(',', $selectedCategories);

        // Handle Address (same as PickupRequestController)
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
            $warehouse = $this->findDonationWarehouseByPincode($pincode, $lat, $lng);

            if (!$warehouse && $user->phone === '9999999999') {
                $warehouse = Warehouse::where('status', true)
                    ->where('accepts_donation', true)
                    ->orderBy('id')
                    ->first();
            }

            if (!$warehouse) {
                DB::rollBack();
                return $this->validationErrorResponse([
                    'warehouse' => ['No warehouse is currently enabled for donation bookings in your area.'],
                ]);
            }

            $pickup = PickupRequest::create([
                'request_type' => 'donation',
                'donation_category' => $donationCategory,
                'pickup_code' => 'DON-' . strtoupper(Str::random(6)) . '-' . rand(1000, 9999),
                'customer_id' => $user->id,
                'address_id' => $request->address_id,
                'warehouse_id' => $warehouse ? $warehouse->id : null,
                'created_by' => $user->id,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone,
                'city_id' => $cityId,
                'address' => $addressStr,
                'latitude' => $lat,
                'longitude' => $lng,
                'scheduled_at' => $request->scheduled_at,
                'metadata' => ['notes' => $request->notes],
                'status' => 'pending',
                'estimated_amount' => null, // No pricing for donation
            ]);

            // Handle Image Upload
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('pickup_requests', 'public');
                    \App\Models\PickupImage::create([
                        'pickup_request_id' => $pickup->id,
                        'image_path' => $path,
                        'type' => 'item'
                    ]);
                }
            }

            foreach ($request->items as $itemData) {
                $pickupItem = PickupItem::create([
                    'pickup_request_id' => $pickup->id,
                    'category_id' => $itemData['category_id'] ?? null,
                    'product_name' => $itemData['product_name'] ?? null,
                    'weight' => $itemData['weight'] ?? 0,
                    'quantity' => $itemData['quantity'] ?? 1,
                    'price_per_unit' => 0,
                    'total_price' => 0,
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
            }

            DB::commit();

            return $this->successResponse('donation.created', $pickup->load('items', 'images'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('donation.create_failed', 500, ['error' => $e->getMessage()]);
        }
    }

    #[OA\Get(
        path: "/api/donation-requests",
        operationId: "getDonationRequests",
        tags: ["Donation"],
        summary: "List donation pickup requests",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of donations")
        ]
    )]
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PickupRequest::where('request_type', 'donation')
            ->with(['items', 'images'])
            ->orderBy('created_at', 'desc');

        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        }

        $requests = $query->paginate(20);
        return $this->paginatedResponse('donation.fetched', $requests);
    }

    #[OA\Post(
        path: "/api/pickup-requests/{id}/clone-as-donation",
        operationId: "cloneAsDonation",
        tags: ["Donation"],
        summary: "Clone an existing scrap pickup as a donation",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "scheduled_at", type: "string", format: "date-time"),
                    new OA\Property(property: "notes", type: "string")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Donation Created")
        ]
    )]
    public function cloneAsDonation(Request $request, $id)
    {
        $source = PickupRequest::with(['items', 'images'])->findOrFail($id);
        $user = Auth::user();

        if ($source->customer_id != $user->id) {
            return $this->errorResponse('pickup.unauthorized', 403);
        }

        DB::beginTransaction();
        try {
            $donation = PickupRequest::create([
                'request_type' => 'donation',
                'donation_category' => 'others', // Default for clones
                'pickup_code' => 'DON-' . strtoupper(Str::random(6)) . '-' . rand(1000, 9999),
                'customer_id' => $user->id,
                'address_id' => $source->address_id,
                'warehouse_id' => $source->warehouse_id,
                'created_by' => $user->id,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone,
                'city_id' => $source->city_id,
                'address' => $source->address,
                'latitude' => $source->latitude,
                'longitude' => $source->longitude,
                'scheduled_at' => $request->scheduled_at ?? now()->addDay(),
                'metadata' => ['notes' => $request->notes ?? 'Cloned from ' . $source->pickup_code],
                'status' => 'pending',
                'estimated_amount' => null,
            ]);

            foreach ($source->items as $item) {
                PickupItem::create([
                    'pickup_request_id' => $donation->id,
                    'category_id' => $item->category_id,
                    'weight' => $item->weight,
                    'quantity' => $item->quantity,
                    'price_per_unit' => 0,
                    'total_price' => 0,
                ]);
            }

            // Optional: Copy images if requested
            if ($request->reuse_images && $source->images->count() > 0) {
                foreach ($source->images as $img) {
                    \App\Models\PickupImage::create([
                        'pickup_request_id' => $donation->id,
                        'image_path' => $img->image_path,
                        'type' => 'item'
                    ]);
                }
            }

            DB::commit();
            return $this->successResponse('donation.cloned', $donation->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('donation.clone_failed', 500, $e->getMessage());
        }
    }

    protected function findDonationWarehouseByPincode(?string $pincode, $lat = null, $lng = null): ?Warehouse
    {
        $normalized = Warehouse::normalizePincode($pincode);
        $requestLat = is_numeric($lat) ? (float) $lat : null;
        $requestLng = is_numeric($lng) ? (float) $lng : null;

        if (!$normalized && $requestLat !== null && $requestLng !== null) {
            $geo = app(LocationService::class)->reverseGeocode($requestLat, $requestLng);
            $normalized = Warehouse::normalizePincode($geo['pincode'] ?? null);
        }

        return Warehouse::findBestByPincode($normalized, $requestLat, $requestLng, 'donation');
    }
}
