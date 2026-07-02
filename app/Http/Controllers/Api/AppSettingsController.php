<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\CategoryType;
use App\Models\HomeBanner;
use App\Models\Warehouse;
use App\Services\LocationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use OpenApi\Attributes as OA;

class AppSettingsController extends Controller
{
    use ApiResponseTrait;

    #[OA\Post(
        path: "/api/app-settings",
        operationId: "getAppSettings",
        tags: ["Settings"],
        summary: "Get global app settings and feature flags with serviceability check",
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "latitude", type: "number", example: 19.0760),
                    new OA\Property(property: "longitude", type: "number", example: 72.8777),
                    new OA\Property(property: "pincode", type: "string", example: "400001"),
                    new OA\Property(property: "location_name", type: "string", example: "Mumbai"),
                    new OA\Property(property: "fcm_token", type: "string", example: "fcm_token_here")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Settings fetched successfully")
        ]
    )]
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'pincode' => 'nullable|string|max:10',
            'location_name' => 'nullable|string',
            'fcm_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = Auth::guard('sanctum')->user();
        
        if ($user) {
            $updateData = [];
            if ($request->filled('fcm_token')) {
                $updateData['fcm_token'] = $request->fcm_token;
            }
            if ($request->latitude && $request->longitude) {
                $updateData['latitude'] = $request->latitude;
                $updateData['longitude'] = $request->longitude;
                $updateData['location_updated_at'] = now();
            }
            if (!empty($updateData)) {
                $user->update($updateData);
            }
        }
        
        // Service Availability Logic
        $serviceAvailability = [
            'is_serviceable' => false,
            'service_type' => 'scrap_pickup',
            'location_name' => $request->location_name ?? 'Unknown',
            'pincode' => null,
            'matched_warehouse_id' => null,
            'matched_warehouse_name' => null,
            'matched_warehouses' => [],
            'message' => trans('service.not_available'),
        ];

        $resolvedPincode = Warehouse::normalizePincode($request->input('pincode'));

        if (!$resolvedPincode && $request->latitude && $request->longitude) {
            try {
                $geo = app(LocationService::class)->reverseGeocode((float) $request->latitude, (float) $request->longitude);
                $resolvedPincode = Warehouse::normalizePincode($geo['pincode'] ?? null);
                if ($resolvedPincode && !$request->filled('location_name')) {
                    $serviceAvailability['location_name'] = $geo['formatted_address'] ?? "Pincode {$resolvedPincode}";
                }
            } catch (\Exception $e) {
                $serviceAvailability['message'] = "Service status unavailable (" . $e->getMessage() . "). Please check back later.";
            }
        }

        if ($resolvedPincode) {
            try {
                $requestLat = $request->filled('latitude') ? (float) $request->latitude : null;
                $requestLng = $request->filled('longitude') ? (float) $request->longitude : null;
                $matchingWarehouses = Warehouse::matchingByPincode($resolvedPincode, 'scrap_pickup');

                if ($matchingWarehouses->isNotEmpty()) {
                    $matchedWarehouses = $matchingWarehouses
                        ->map(function (Warehouse $warehouse) use ($requestLat, $requestLng) {
                            $distanceKm = null;
                            if (
                                $requestLat !== null && $requestLng !== null &&
                                $warehouse->latitude !== null && $warehouse->longitude !== null
                            ) {
                                $distanceKm = round($this->calculateDistanceKm(
                                    $requestLat,
                                    $requestLng,
                                    (float) $warehouse->latitude,
                                    (float) $warehouse->longitude
                                ), 2);
                            }

                            return [
                                'id' => $warehouse->id,
                                'name' => $warehouse->name,
                                'code' => $warehouse->code,
                                'pincodes' => $warehouse->service_pincodes ?? [],
                                'distance_km' => $distanceKm,
                            ];
                        })
                        ->sortBy(fn ($warehouse) => $warehouse['distance_km'] ?? PHP_FLOAT_MAX)
                        ->values()
                        ->all();

                    $serviceAvailability = [
                        'is_serviceable' => true,
                        'service_type' => 'scrap_pickup',
                        'location_name' => $serviceAvailability['location_name'] ?: "Pincode {$resolvedPincode}",
                        'pincode' => $resolvedPincode,
                        'matched_warehouse_id' => $matchedWarehouses[0]['id'] ?? $matchingWarehouses->first()->id,
                        'matched_warehouse_name' => $matchedWarehouses[0]['name'] ?? $matchingWarehouses->first()->name,
                        'matched_warehouses' => $matchedWarehouses,
                        'message' => trans('service.available'),
                    ];
                }
            } catch (\Exception $e) {
                $serviceAvailability['message'] = "Service status unavailable (" . $e->getMessage() . "). Please check back later.";
            }
        }

        // Dummy test customer (9999999999) can access all functionality
        // regardless of location/pincode serviceability.
        if ($user && $user->phone === '9999999999' && !$serviceAvailability['is_serviceable']) {
            $fallbackWarehouse = Warehouse::where('status', true)->orderBy('id')->first();

            $serviceAvailability = [
                'is_serviceable' => true,
                'service_type' => 'scrap_pickup',
                'location_name' => $serviceAvailability['location_name'] ?: 'Test Location',
                'pincode' => $resolvedPincode,
                'matched_warehouse_id' => $fallbackWarehouse?->id,
                'matched_warehouse_name' => $fallbackWarehouse?->name,
                'matched_warehouses' => $fallbackWarehouse ? [[
                    'id' => $fallbackWarehouse->id,
                    'name' => $fallbackWarehouse->name,
                    'code' => $fallbackWarehouse->code,
                    'pincodes' => $fallbackWarehouse->service_pincodes ?? [],
                    'distance_km' => null,
                ]] : [],
                'message' => trans('service.available'),
            ];
        }

        $data = [
            'language' => $user ? $user->language : App::getLocale(),
            'supported_languages' => AppSetting::get('supported_languages', ['en', 'hi', 'gu']),
            'features' => [
                'donation_enabled' => AppSetting::get('donation_enabled', true),
                'scrap_pickup_enabled' => AppSetting::get('scrap_pickup_enabled', true),
                'wallet_enabled' => AppSetting::get('wallet_enabled', false),
            ],
            'settings' => [
                'default_city_id' => AppSetting::get('default_city_id', 1),
                'minimum_free_pickup_amount' => AppSetting::get('minimum_free_pickup_amount', 1500),
                'low_value_shipping_charge' => AppSetting::get('low_value_shipping_charge', 100),
                'customer_support_number' => AppSetting::get('customer_support_number', '+91 00000 00000'),
                'support_phone_number' => AppSetting::get('support_phone', '+91 00000 00000'),
                'app_version' => AppSetting::get('app_version', '1.0.3'),
                'latest_version' => AppSetting::get('latest_version', '2.0.0'),
                'min_version' => AppSetting::get('min_version', '1.0.0'),
                'force_update' => (bool) AppSetting::get('force_update', false),
                'android_url' => AppSetting::get('android_url', 'https://play.google.com/store/apps/details?id=com.abhyuthanam.scrapify'),
                'ios_url' => AppSetting::get('ios_url', 'https://apps.apple.com/us/app/scrapify/id6775160804'),
                'pickup_boy_location_interval_seconds' => AppSetting::get('pickup_boy_location_interval_seconds', 30),
                'location_update_interval_seconds' => AppSetting::get('pickup_boy_location_interval_seconds', 30),
                'tracking_refresh_interval_seconds' => AppSetting::get('tracking_refresh_interval_seconds', 20),
                'dashboard_refresh_interval_seconds' => AppSetting::get('dashboard_refresh_interval_seconds', 60),
                'verification_required' => AppSetting::get('verification_required', true),
                'verification_enabled' => AppSetting::get('verification_required', true),
                'manual_item_add_edit_enabled' => AppSetting::get('manual_item_add_edit_enabled', true),
                'bill_generation_enabled' => AppSetting::get('bill_generation_enabled', true),
                'qr_verification_enabled' => AppSetting::get('qr_verification_enabled', true),
                'reschedule_enabled' => AppSetting::get('reschedule_enabled', true),
                'max_reschedule_hours_before_slot' => AppSetting::get('max_reschedule_hours_before_slot', 2),
                'donation_products' => AppSetting::get('donation_products', ['Cloth', 'Shoes', 'Toys', 'Books']),
                'corporate_categories' => CategoryType::where('status', true)
                    ->where('show_in_corporate_booking', true)
                    ->orderBy('id')
                    ->get()
                    ->map(fn(CategoryType $type) => $type->getTranslatedName())
                    ->values()
                    ->all(),
                'corporate_meeting_types' => AppSetting::get('corporate_meeting_types', ['in_person', 'google_meet', 'skype']),
                'scrap_proof_images_required' => AppSetting::get('scrap_proof_images_required', true),
                'scrap_proof_image_labels' => AppSetting::get('scrap_proof_image_labels', ['front', 'back', 'left', 'right']),
                'home_banners' => HomeBanner::orderBy('sort_order')
                    ->get()
                    ->map(fn (HomeBanner $banner) => [
                        'image_url' => $banner->image_url,
                        'text' => $banner->text ?? '',
                    ])
                    ->values()
                    ->all(),
            ],
            'service_availability' => $serviceAvailability,
        ];

        return $this->successResponse('app_settings.fetched', $data);
    }

    private function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;

        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));

        return $earthRadius * $c;
    }

    #[OA\Post(
        path: "/api/app-settings/language",
        operationId: "updateAppLanguage",
        tags: ["Settings"],
        summary: "Update user's preferred language",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["language"],
                properties: [
                    new OA\Property(property: "language", type: "string", enum: ["en", "hi", "gu"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Language updated successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function updateLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|in:en,hi,gu',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = Auth::user();
        $user->language = $request->language;
        $user->save();

        return $this->successResponse('app_settings.language_updated', [
            'language' => $user->language
        ]);
    }
}
