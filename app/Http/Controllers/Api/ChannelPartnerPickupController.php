<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartnerCustomer;
use App\Models\PickupImage;
use App\Models\PickupItem;
use App\Models\PickupRequest;
use App\Models\PickupStatusLog;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\HomeAppliancePricingService;
use App\Services\LocationService;
use App\Services\PickupAssignmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ChannelPartnerPickupController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly HomeAppliancePricingService $pricingService,
        private readonly PickupAssignmentService $assignmentService
    ) {}

    public function store(Request $request)
    {
        $partnerId = $this->partnerId($request);

        $validator = Validator::make($request->all(), [
            'request_type' => 'required|in:corporate,basic_scrap,scrap',
            'customer_type' => 'required|in:individual,corporate',
            'customer_id' => 'nullable|exists:channel_partner_customers,id',
            'customer' => 'required_without:customer_id|array',
            'customer.name' => 'required_without:customer_id|string|max:255',
            'customer.mobile' => 'required_without:customer_id|string|max:20',
            'customer.address' => 'nullable|string',
            'customer.city' => 'nullable|string|max:255',
            'customer.pincode' => 'nullable|string|max:10',
            'customer.landmark' => 'nullable|string|max:255',
            'customer.latitude' => 'nullable|numeric',
            'customer.longitude' => 'nullable|numeric',
            'pincode' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string|max:1000',
            'payout_method' => 'nullable|string|in:upi,bank,cash,wallet',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.subcategory_id' => 'nullable|exists:categories,id',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:20',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.estimated_weight' => 'nullable|numeric|min:0',
            'items.*.condition' => 'nullable|string|max:255',
            'items.*.remarks' => 'nullable|string',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.images' => 'nullable|array',
            'items.*.images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        return DB::transaction(function () use ($request, $partnerId) {
            $customer = $this->resolveCustomer($request, $partnerId);
            $address = $request->address ?: $customer->address;
            $latitude = $request->latitude ?? $customer->latitude;
            $longitude = $request->longitude ?? $customer->longitude;
            $pincode = $request->input('pincode') ?: $customer->pincode;
            $warehouse = $this->resolveWarehouseByPincode($pincode, $latitude, $longitude);

            if (!$warehouse) {
                return $this->validationErrorResponse([
                    'warehouse' => ['No active warehouse is mapped for this booking pincode. Please add the pincode in warehouse service pincodes before accepting bookings.'],
                    'pincode' => [$pincode ?: 'Pincode could not be resolved from the selected address/location.'],
                ]);
            }

            $normalizedRequestType = $request->request_type === 'basic_scrap' ? 'scrap' : $request->request_type;
            $pickup = PickupRequest::create([
                'request_type' => $normalizedRequestType,
                'pickup_code' => 'CP-' . strtoupper(Str::random(6)) . '-' . rand(1000, 9999),
                'customer_id' => $customer->app_customer_id ?? $request->user()->id,
                'partner_customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'created_by' => $request->user()->id,
                'channel_partner_id' => $partnerId,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->mobile,
                'city_id' => $request->city_id,
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'scheduled_at' => $request->scheduled_at,
                'payout_method' => $request->payout_method ?? 'cash',
                'status' => 'pending',
                'estimated_amount' => 0,
                'metadata' => [
                    'partner_notes' => $request->notes,
                    'customer_type' => $request->customer_type,
                    'request_type_input' => $request->request_type,
                    'creation_channel' => 'partner_mobile',
                ],
            ]);

            PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'pending',
                'notes' => $request->notes ?? 'Pickup created by channel partner.',
                'created_by' => $request->user()->id,
            ]);

            $total = 0;
            foreach ($request->items as $index => $itemData) {
                $weight = (float) ($itemData['estimated_weight'] ?? $itemData['weight'] ?? 0);
                $quantity = (float) ($itemData['quantity'] ?? 1);
                $rate = (float) ($itemData['estimated_price'] ?? 0);
                $selectedCategoryId = !empty($itemData['subcategory_id']) ? (int) $itemData['subcategory_id'] : (int) $itemData['category_id'];

                if (!$rate && $selectedCategoryId > 0) {
                    $rate = (float) $this->pricingService->estimate($selectedCategoryId, []);
                }

                $lineTotal = $rate > 0 ? ($weight > 0 ? $rate * $weight : $rate * $quantity) : 0;

                $item = PickupItem::create([
                    'pickup_request_id' => $pickup->id,
                    'category_id' => $selectedCategoryId,
                    'product_name' => $itemData['product_name'] ?? null,
                    'quantity' => $quantity,
                    'weight' => $weight,
                    'condition' => $itemData['condition'] ?? null,
                    'price_per_unit' => $rate,
                    'total_price' => $lineTotal,
                    'remarks' => trim(($itemData['remarks'] ?? '') . ' | unit:' . ($itemData['unit'] ?? '')),
                ]);

                foreach ($request->file("items.{$index}.images", []) as $image) {
                    PickupImage::create([
                        'pickup_request_id' => $pickup->id,
                        'pickup_item_id' => $item->id,
                        'image_path' => $image->store('pickup_items', 'public'),
                        'type' => 'item',
                        'remarks' => $itemData['remarks'] ?? null,
                    ]);
                }

                $total += $lineTotal;
            }

            foreach ($request->file('images', []) as $image) {
                PickupImage::create([
                    'pickup_request_id' => $pickup->id,
                    'image_path' => $image->store('pickup_images', 'public'),
                    'type' => 'item',
                ]);
            }

            $pickup->update(['estimated_amount' => $total]);

            return $this->successResponse(
                'pickup.created',
                $pickup->fresh()->load(['partnerCustomer', 'items.category', 'images']),
                201
            );
        });
    }

    public function availablePickupBoys(Request $request, $pickupId = null)
    {
        $partnerId = $this->partnerId($request);

        $boys = User::role('pickup_boy')
            ->where('channel_partner_id', $partnerId)
            ->where('status', true)
            ->where('is_available', true)
            ->withCount(['assignments as today_assigned_count' => fn ($q) => $q
                ->whereDate('assigned_at', now()->toDateString())
                ->whereNotIn('status', ['cancelled', 'rejected', 'reassigned'])])
            ->get()
            ->map(fn ($boy) => [
                'id' => $boy->id,
                'name' => $boy->name,
                'phone' => $boy->phone,
                'is_online' => (bool) $boy->is_online,
                'is_available' => (bool) $boy->is_available,
                'daily_capacity' => $boy->daily_capacity,
                'today_assigned_count' => $boy->today_assigned_count,
                'capacity_full' => $boy->today_assigned_count >= $boy->daily_capacity,
            ]);

        return $this->successResponse('partner.pickup_boys_fetched', $boys);
    }

    public function assign(Request $request, $id)
    {
        $request->validate([
            'pickup_boy_id' => 'required|exists:users,id',
            'remarks' => 'nullable|string',
            'override_capacity' => 'nullable|boolean',
        ]);

        $pickup = PickupRequest::where('channel_partner_id', $this->partnerId($request))->findOrFail($id);
        $boy = User::role('pickup_boy')
            ->where('channel_partner_id', $pickup->channel_partner_id)
            ->findOrFail($request->pickup_boy_id);

        $result = $this->assignmentService->assign(
            $pickup,
            $boy,
            $request->user(),
            'channel_partner',
            (bool) $request->boolean('override_capacity')
        );

        if (!$result['ok']) {
            return $this->errorResponse($result['message'], 422);
        }

        if ($request->filled('remarks')) {
            $result['assignment']->update(['remarks' => $request->remarks]);
        }

        return $this->successResponse($result['message'], $result['assignment']->fresh()->load('pickupBoy'));
    }

    public function deliverToWarehouse(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'final_weight' => 'required|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'proof_images' => 'nullable|array',
            'proof_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $pickup = PickupRequest::where('channel_partner_id', $this->partnerId($request))->findOrFail($id);

        DB::transaction(function () use ($pickup, $request) {
            $metadata = $pickup->metadata ?? [];
            $metadata['warehouse_delivery'] = [
                'final_weight' => $request->final_weight,
                'remarks' => $request->remarks,
                'submitted_at' => now()->toDateTimeString(),
                'submitted_by' => $request->user()->id,
            ];

            $pickup->update([
                'final_amount' => $request->final_amount,
                'status' => 'delivered_to_warehouse',
                'metadata' => $metadata,
            ]);

            $pickup->assignment?->update([
                'status' => 'delivered_to_warehouse',
                'completed_at' => now(),
            ]);

            foreach ($request->file('proof_images', []) as $image) {
                PickupImage::create([
                    'pickup_request_id' => $pickup->id,
                    'image_path' => $image->store('pickup_delivery_proofs', 'public'),
                    'type' => 'delivery_proof',
                    'remarks' => $request->remarks,
                ]);
            }

            PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'delivered_to_warehouse',
                'notes' => $request->remarks ?? 'Material delivered to warehouse.',
                'created_by' => $request->user()->id,
            ]);

            // Create Settlement record
            $commissionRate = 10.00; // Default 10%
            $commissionAmount = Settlement::calculateCommission($request->final_amount, $commissionRate);
            $netAmount = $request->final_amount - $commissionAmount;

            Settlement::create([
                'partner_id' => $request->user()->id,
                'pickup_request_id' => $pickup->id,
                'total_amount' => $request->final_amount,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'net_amount' => $netAmount,
                'status' => 'pending',
                'payout_status' => 'pending',
                'notes' => 'Settlement created automatically after delivery.',
            ]);
        });

        return $this->successResponse('pickup.delivered_to_warehouse', $pickup->fresh()->load('images', 'statusLogs'));
    }

    public function tracking(Request $request, $id)
    {
        $pickup = PickupRequest::where('channel_partner_id', $this->partnerId($request))
            ->with(['partnerCustomer', 'assignment.pickupBoy', 'statusLogs.creator'])
            ->findOrFail($id);

        return $this->successResponse('pickup.tracking', [
            'pickup_id' => $pickup->id,
            'pickup_code' => $pickup->pickup_code,
            'status' => $pickup->status,
            'customer' => $pickup->partnerCustomer,
            'pickup_boy' => $pickup->assignment?->pickupBoy,
            'timeline' => $pickup->statusLogs
                ->sortBy('created_at')
                ->values()
                ->map(fn ($log) => [
                    'status' => $log->status,
                    'label' => ucwords(str_replace('_', ' ', $log->status)),
                    'notes' => $log->notes,
                    'created_by' => $log->creator?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function settlements(Request $request)
    {
        $query = Settlement::where('partner_id', $request->user()->id)
            ->with('pickupRequest.partnerCustomer');

        if ($request->filled('status')) {
            $query->where('payout_status', $request->status);
        }

        return $this->paginatedResponse('settlements.fetched', $query->latest()->paginate($request->per_page ?? 20));
    }

    private function resolveCustomer(Request $request, int $partnerId): ChannelPartnerCustomer
    {
        if ($request->filled('customer_id')) {
            $customer = ChannelPartnerCustomer::where('channel_partner_id', $partnerId)->findOrFail($request->customer_id);
            $appCustomer = $this->ensureCustomerUser(
                (string) $customer->mobile,
                (string) $customer->name,
                $customer->latitude,
                $customer->longitude
            );
            $customer->app_customer_id = $appCustomer->id;
            return $customer;
        }
        $customer = ChannelPartnerCustomer::firstOrCreate(
            ['channel_partner_id' => $partnerId, 'mobile' => $request->input('customer.mobile')],
            [
                'name' => $request->input('customer.name'),
                'address' => $request->input('customer.address'),
                'city' => $request->input('customer.city'),
                'pincode' => $request->input('customer.pincode'),
                'landmark' => $request->input('customer.landmark'),
                'latitude' => $request->input('customer.latitude'),
                'longitude' => $request->input('customer.longitude'),
            ]
        );
        $appCustomer = $this->ensureCustomerUser(
            (string) $request->input('customer.mobile'),
            (string) $request->input('customer.name'),
            $request->input('customer.latitude'),
            $request->input('customer.longitude')
        );
        $customer->app_customer_id = $appCustomer->id;
        return $customer;
    }

    private function ensureCustomerUser(
        string $mobile,
        string $name,
        mixed $latitude = null,
        mixed $longitude = null
    ): User {
        $normalizedMobile = preg_replace('/\D+/', '', $mobile) ?? '';
        $normalizedMobile = substr($normalizedMobile, -10);
        $normalizedName = trim($name) !== '' ? trim($name) : 'Customer';
        $domain = app()->environment('production') ? 'scrapi5.com' : 'test.com';

        $user = User::firstOrCreate(
            ['phone' => $normalizedMobile],
            [
                'name' => $normalizedName,
                'email' => $normalizedMobile . '@' . $domain,
                'password' => bcrypt($normalizedMobile),
                'status' => true,
            ]
        );

        if (!$user->hasRole('customer')) {
            $user->assignRole('customer');
        }

        $updates = [];
        if (trim((string) $user->name) === '' || $user->name === 'Customer') {
            $updates['name'] = $normalizedName;
        }
        if ($latitude !== null && $longitude !== null) {
            $updates['latitude'] = $latitude;
            $updates['longitude'] = $longitude;
        }
        if (!empty($updates)) {
            $user->update($updates);
        }

        return $user;
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

    private function partnerId(Request $request): int
    {
        abort_unless($request->user()->channel_partner_id, 403, 'Channel partner profile not found.');

        return (int) $request->user()->channel_partner_id;
    }
}
