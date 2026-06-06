<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ChannelPartnerCustomer;
use App\Models\PickupImage;
use App\Models\PickupItem;
use App\Models\PickupRequest;
use App\Models\PickupStatusLog;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\HomeAppliancePricingService;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PartnerPickupController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('channel_partner') || $user->hasRole('admin') || $user->hasRole('warehouse'), 403);

        $customers = $user->hasRole('channel_partner')
            ? ChannelPartnerCustomer::where('channel_partner_id', $user->channel_partner_id)
                ->orderBy('name')
                ->get()
                ->map(fn (ChannelPartnerCustomer $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'mobile' => $customer->mobile,
                    'address' => $customer->address,
                    'city' => $customer->city,
                    'pincode' => $customer->pincode,
                    'landmark' => $customer->landmark,
                    'latitude' => $customer->latitude,
                    'longitude' => $customer->longitude,
                    'source' => 'partner_customer',
                ])
            : User::role('customer')
                ->select('id', 'name', 'phone')
                ->orderBy('name')
                ->limit(500)
                ->get()
                ->map(fn (User $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'mobile' => $customer->phone,
                    'address' => null,
                    'city' => null,
                    'pincode' => null,
                    'landmark' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'source' => 'app_customer',
                ]);

        return Inertia::render('Admin/PartnerPickups/Form', [
            'customers' => $customers,
            'categories' => Category::query()
                ->where('status', true)
                ->whereNull('parent_id')
                ->with(['children' => fn ($query) => $query->where('status', true)->orderBy('name->en')])
                ->orderBy('name->en')
                ->get(),
        ]);
    }

    public function store(Request $request, HomeAppliancePricingService $pricingService)
    {
        $actor = $request->user();
        $isPartner = $actor->hasRole('channel_partner');
        abort_unless($isPartner || $actor->hasRole('admin') || $actor->hasRole('warehouse'), 403);

        $data = $request->validate([
            'request_type' => 'required|in:corporate,basic_scrap,scrap',
            'customer_type' => 'required|in:individual,corporate',
            'customer_id' => 'nullable|integer',
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
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.subcategory_id' => 'nullable|exists:categories,id',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:20',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.condition' => 'nullable|string|max:255',
            'items.*.remarks' => 'nullable|string',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.images' => 'nullable|array',
            'items.*.images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $partnerId = $isPartner ? $actor->channel_partner_id : null;
        $normalizedRequestType = $data['request_type'] === 'basic_scrap' ? 'scrap' : $data['request_type'];

        $pickup = DB::transaction(function () use ($request, $data, $partnerId, $pricingService, $actor, $isPartner, $normalizedRequestType) {
            $customer = $this->resolveCustomer($request, $partnerId, $isPartner);
            $address = $request->address ?: $customer->address;
            $latitude = $request->latitude ?? $customer->latitude;
            $longitude = $request->longitude ?? $customer->longitude;
            $pincode = $request->input('pincode') ?: $customer->pincode;
            $warehouse = $this->resolveWarehouseByPincode($pincode, $latitude, $longitude);

            $metadata = [
                'partner_notes' => $data['notes'] ?? null,
                'customer_type' => $data['customer_type'],
                'creation_channel' => $isPartner ? 'partner_web' : ($actor->hasRole('warehouse') ? 'warehouse_web' : 'admin_web'),
                'request_type_input' => $data['request_type'],
            ];

            $pickup = PickupRequest::create([
                'request_type' => $normalizedRequestType,
                'pickup_code' => 'CP-' . strtoupper(Str::random(6)) . '-' . rand(1000, 9999),
                'customer_id' => $customer->app_customer_id,
                'partner_customer_id' => $customer->partner_customer_id,
                'warehouse_id' => $warehouse?->id,
                'created_by' => $actor->id,
                'channel_partner_id' => $partnerId,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->mobile,
                'city_id' => $request->city_id,
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'scheduled_at' => $data['scheduled_at'],
                'payout_method' => 'cash',
                'status' => 'pending',
                'estimated_amount' => 0,
                'metadata' => $metadata,
            ]);

            $total = 0;
            foreach ($data['items'] as $index => $itemData) {
                $weight = (float) ($itemData['weight'] ?? 0);
                $qty = (int) ($itemData['quantity'] ?? 1);
                $rate = (float) ($itemData['estimated_price'] ?? 0);

                $selectedCategoryId = !empty($itemData['subcategory_id']) ? (int) $itemData['subcategory_id'] : (int) $itemData['category_id'];

                if (!$rate && $selectedCategoryId > 0) {
                    $rate = (float) $pricingService->estimate($selectedCategoryId, []);
                }

                $lineTotal = $rate > 0 ? ($weight > 0 ? $rate * $weight : $rate * $qty) : 0;
                $total += $lineTotal;

                $item = PickupItem::create([
                    'pickup_request_id' => $pickup->id,
                    'category_id' => $selectedCategoryId,
                    'product_name' => $itemData['product_name'] ?? null,
                    'quantity' => $qty,
                    'weight' => $weight,
                    'condition' => $itemData['condition'] ?? null,
                    'price_per_unit' => $rate,
                    'total_price' => $lineTotal,
                    'remarks' => trim(($itemData['remarks'] ?? '') . ' | unit:' . ($itemData['unit'] ?? '')),
                ]);

                if ($request->hasFile("items.{$index}.images")) {
                    foreach ($request->file("items.{$index}.images") as $image) {
                        PickupImage::create([
                            'pickup_request_id' => $pickup->id,
                            'pickup_item_id' => $item->id,
                            'image_path' => $image->store('pickup_items', 'public'),
                            'type' => 'item',
                            'remarks' => $itemData['remarks'] ?? null,
                        ]);
                    }
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    PickupImage::create([
                        'pickup_request_id' => $pickup->id,
                        'image_path' => $image->store('pickup_images', 'public'),
                        'type' => 'item',
                    ]);
                }
            }

            $pickup->update(['estimated_amount' => $total]);

            PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'pending',
                'notes' => $data['notes'] ?? 'Pickup created by channel partner.',
                'created_by' => $actor->id,
            ]);

            return $pickup;
        });

        return redirect()->route('admin.pickups.show', $pickup->id)->with('success', 'Pickup request created successfully.');
    }

    public function deliverToWarehouse(Request $request, $id)
    {
        $request->validate([
            'final_weight' => 'required|numeric|min:0',
            'final_amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'proof_images' => 'nullable|array',
            'proof_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $pickup = PickupRequest::where('channel_partner_id', $request->user()->channel_partner_id)->findOrFail($id);

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

            if ($request->hasFile('proof_images')) {
                foreach ($request->file('proof_images') as $image) {
                    PickupImage::create([
                        'pickup_request_id' => $pickup->id,
                        'image_path' => $image->store('pickup_delivery_proofs', 'public'),
                        'type' => 'delivery_proof',
                        'remarks' => $request->remarks,
                    ]);
                }
            }

            PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'delivered_to_warehouse',
                'notes' => $request->remarks ?? 'Material delivered to warehouse.',
                'created_by' => $request->user()->id,
            ]);

            // Create Settlement record
            $commissionRate = 10.00; // Default 10% or fetch from partner settings if available
            $commissionAmount = \App\Models\Settlement::calculateCommission($request->final_amount, $commissionRate);
            $netAmount = $request->final_amount - $commissionAmount;

            \App\Models\Settlement::create([
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

        return back()->with('success', 'Material delivered to warehouse successfully.');
    }

    private function resolveCustomer(Request $request, ?int $partnerId, bool $isPartner): object
    {
        if ($isPartner) {
            if ($request->filled('customer_id')) {
                $customer = ChannelPartnerCustomer::where('channel_partner_id', $partnerId)->findOrFail($request->customer_id);
                $appCustomer = $this->ensureCustomerUser(
                    $customer->mobile,
                    $customer->name,
                    $customer->latitude,
                    $customer->longitude
                );
                return (object) [
                    'name' => $customer->name,
                    'mobile' => $customer->mobile,
                    'address' => $customer->address,
                    'latitude' => $customer->latitude,
                    'longitude' => $customer->longitude,
                    'partner_customer_id' => $customer->id,
                    'app_customer_id' => $appCustomer->id,
                ];
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

            return (object) [
                'name' => $customer->name,
                'mobile' => $customer->mobile,
                'address' => $customer->address,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'partner_customer_id' => $customer->id,
                'app_customer_id' => $appCustomer->id,
            ];
        }

        $appCustomer = null;
        if ($request->filled('customer_id')) {
            $appCustomer = User::role('customer')->where('id', $request->customer_id)->first();
        }
        if ($appCustomer === null) {
            $mobile = (string) $request->input('customer.mobile');
            $name = (string) $request->input('customer.name');
            if ($mobile !== '') {
                $appCustomer = $this->ensureCustomerUser(
                    $mobile,
                    $name !== '' ? $name : 'Customer',
                    $request->input('customer.latitude'),
                    $request->input('customer.longitude')
                );
            }
        }

        return (object) [
            'name' => $appCustomer?->name ?? $request->input('customer.name'),
            'mobile' => $appCustomer?->phone ?? $request->input('customer.mobile'),
            'address' => $request->input('customer.address'),
            'latitude' => $request->input('customer.latitude'),
            'longitude' => $request->input('customer.longitude'),
            'partner_customer_id' => null,
            'app_customer_id' => $appCustomer?->id,
        ];
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
}
