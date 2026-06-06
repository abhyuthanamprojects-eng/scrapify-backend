<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\PickupItem;
use App\Models\PickupRequestAttribute;
use App\Models\AppSetting;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\Warehouse;

class CorporateBookingController extends Controller
{
    use ApiResponseTrait;

    public function options()
    {
        $corporateTypes = $this->getEnabledCorporateTypes();
        $categories = $corporateTypes
            ->map(fn(CategoryType $type) => trim((string) $type->getTranslatedName()))
            ->filter()
            ->values()
            ->all();
        $meetingTypes = collect(AppSetting::get('corporate_meeting_types', ['in_person', 'google_meet', 'skype']))
            ->map(fn($item) => Str::lower(trim((string) $item)))
            ->filter()
            ->values()
            ->all();

        $scrapCategories = $corporateTypes
            ->map(fn($type) => [
                'id' => $type->id,
                'name' => $type->getTranslatedName(),
                'image' => $type->image_url,
                'subcategories' => $type->categories->map(fn($category) => [
                    'id' => $category->id,
                    'name' => $category->getTranslatedName(),
                    'image' => $category->image_url,
                ])->values(),
            ])
            ->values();

        return $this->successResponse('corporate.options_fetched', [
            'categories' => $categories,
            'scrap_categories' => $scrapCategories,
            'meeting_types' => $meetingTypes,
        ]);
    }

    #[OA\Post(
        path: "/api/corporate-bookings",
        operationId: "createCorporateBooking",
        tags: ["Corporate"],
        summary: "Create a new corporate booking request",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 201, description: "Corporate Booking Created"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request)
    {
        $user = Auth::user();
        $enabledCorporateTypes = $this->getEnabledCorporateTypes();
        $allowedCorporateCategories = $enabledCorporateTypes
            ->map(fn(CategoryType $type) => trim((string) $type->getTranslatedName()))
            ->filter()
            ->values()
            ->all();
        $allowedMeetingTypes = collect(AppSetting::get('corporate_meeting_types', ['in_person', 'google_meet', 'skype']))
            ->map(fn($item) => Str::lower(trim((string) $item)))
            ->filter()
            ->values()
            ->all();

        $validator = Validator::make($request->all(), [
            'address_id' => 'nullable|exists:addresses,id',
            'address' => 'required_without:address_id|string',
            'city_id' => 'required_without:address_id|exists:cities,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'scheduled_at' => 'required|date|after:now',
            'company_name' => 'nullable|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_mobile' => 'required|string|regex:/^[6-9]\d{9}$/',
            'contact_email' => 'required|email|max:255',
            'corporate_category' => 'nullable|string',
            'corporate_categories' => 'nullable|array|min:1',
            'corporate_categories.*' => 'required|string',
            'meeting_type' => 'required|string',
            'gst_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'items' => 'nullable|array|min:1',
            'items.*.category_id' => 'required_with:items|exists:categories,id',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.attributes' => 'nullable|array',
            'items.*.attributes.*.attribute_id' => 'required|exists:attributes,id',
            'items.*.attributes.*.value' => 'required',
            'corporate_category_items' => 'nullable|array|min:1',
            'corporate_category_items.*.corporate_category' => 'required|string',
            'corporate_category_items.*.unit' => 'required|string|in:kg,qns',
            'corporate_category_items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }
        if (
            empty($request->items) &&
            empty($request->corporate_category_items) &&
            !$request->filled('corporate_category') &&
            empty($request->corporate_categories)
        ) {
            return $this->validationErrorResponse([
                'items' => ['Either direct corporate items or corporate category quantities are required.'],
            ]);
        }

        $selectedItems = collect($request->input('items', []))
            ->filter(fn($item) => !empty($item['category_id']))
            ->values();
        $selectedCategoryModels = $selectedItems->isEmpty()
            ? collect()
            : Category::with('categoryType')
                ->whereIn('id', $selectedItems->pluck('category_id')->map(fn($id) => (int) $id)->all())
                ->get()
                ->keyBy('id');

        // Backward compatible:
        // - old payload: corporate_category (string)
        // - new payload: corporate_categories (array of strings)
        $requestCategories = collect($request->input('corporate_categories', []))
            ->when(!$request->has('corporate_categories') && $request->filled('corporate_category'), function ($collection) use ($request) {
                return $collection->push($request->input('corporate_category'));
            })
            ->when((!$request->has('corporate_categories') || empty($request->input('corporate_categories'))) && $request->has('corporate_category_items'), function ($collection) use ($request) {
                return $collection->merge(collect($request->input('corporate_category_items', []))->pluck('corporate_category')->all());
            })
            ->when((!$request->has('corporate_categories') || empty($request->input('corporate_categories'))) && $selectedCategoryModels->isNotEmpty(), function ($collection) use ($selectedItems, $selectedCategoryModels) {
                return $collection->merge(
                    $selectedItems
                        ->map(function ($item) use ($selectedCategoryModels) {
                            $category = $selectedCategoryModels->get((int) $item['category_id']);
                            return $category?->categoryType?->getTranslatedName();
                        })
                        ->filter()
                        ->values()
                        ->all()
                );
            })
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($requestCategories)) {
            return $this->validationErrorResponse([
                'corporate_categories' => ['Please select at least one corporate category.'],
            ]);
        }

        $invalidCategories = array_values(array_filter(
            $requestCategories,
            fn($category) => !in_array($category, $allowedCorporateCategories, true)
        ));
        if (!empty($invalidCategories)) {
            return $this->validationErrorResponse([
                'corporate_categories' => ['One or more corporate categories are invalid.'],
                'invalid_corporate_categories' => $invalidCategories,
                'allowed_corporate_categories' => $allowedCorporateCategories,
            ]);
        }
        $primaryCorporateCategory = $requestCategories[0];

        $corporateCategoryItems = collect($request->input('corporate_category_items', []))
            ->map(function ($entry) {
                return [
                    'corporate_category' => trim((string) ($entry['corporate_category'] ?? '')),
                    'unit' => Str::lower(trim((string) ($entry['unit'] ?? ''))),
                    'quantity' => isset($entry['quantity']) ? (float) $entry['quantity'] : 0,
                ];
            })
            ->filter(fn($entry) => !empty($entry['corporate_category']) && $entry['quantity'] > 0)
            ->values()
            ->all();

        if (empty($corporateCategoryItems) && $selectedItems->isNotEmpty()) {
            $corporateCategoryItems = $selectedItems
                ->map(function ($item) use ($selectedCategoryModels) {
                    $category = $selectedCategoryModels->get((int) $item['category_id']);
                    if (!$category) {
                        return null;
                    }

                    $weight = isset($item['weight']) ? (float) $item['weight'] : 0;
                    $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 0;

                    return [
                        'corporate_category' => $category->getTranslatedName(),
                        'category_id' => $category->id,
                        'category_type' => $category->categoryType?->getTranslatedName(),
                        'unit' => $weight > 0 ? 'kg' : 'qns',
                        'quantity' => $weight > 0 ? $weight : max($quantity, 1),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        if (empty($corporateCategoryItems) && $request->filled('corporate_category')) {
            $corporateCategoryItems = [[
                'corporate_category' => $primaryCorporateCategory,
                'unit' => 'kg',
                'quantity' => 0,
            ]];
        }

        $meetingType = Str::lower(trim((string) $request->meeting_type));
        if (!in_array($meetingType, $allowedMeetingTypes, true)) {
            return $this->validationErrorResponse([
                'meeting_type' => ['The selected meeting type is invalid.'],
                'allowed_meeting_types' => $allowedMeetingTypes,
            ]);
        }

        // Handle Address
        $addressStr = $request->address;
        $cityId = $request->city_id;
        $lat = $request->latitude;
        $lng = $request->longitude;

        if ($request->has('address_id')) {
            $addressModel = \App\Models\Address::find($request->address_id);
            if ($addressModel) {
                $addressStr = $addressModel->address_line_1 . ', ' . $addressModel->address_line_2;
                $cityId = $addressModel->city_id;
                $lat = $addressModel->latitude;
                $lng = $addressModel->longitude;
            }
        }

        DB::beginTransaction();

        try {
            // Corporate flow must route to main warehouse/admin queue (not nearest warehouse).
            $warehouse = $this->resolveCorporateWarehouse();
            if (!$warehouse) {
                DB::rollBack();
                return $this->validationErrorResponse([
                    'warehouse' => ['No warehouse is currently enabled for corporate bookings.'],
                ]);
            }

            $pickup = PickupRequest::create([
                'request_type' => 'corporate',
                'pickup_code' => 'CORP-' . strtoupper(Str::random(6)) . '-' . rand(1000, 9999),
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
                'metadata' => [
                    'notes' => $request->notes,
                    'company_name' => $request->company_name,
                    'contact_name' => $request->contact_name,
                    'contact_mobile' => $request->contact_mobile,
                    'contact_email' => $request->contact_email,
                    // keep both keys for compatibility in downstream consumers
                    'corporate_category' => $primaryCorporateCategory,
                    'corporate_categories' => $requestCategories,
                    'corporate_category_items' => $corporateCategoryItems,
                    'meeting_type' => $meetingType,
                    'gst_number' => $request->gst_number,
                ],
                'status' => 'pending',
                'estimated_amount' => null, // Typically quoted later by admin
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

            if (!empty($request->items)) {
                foreach ($request->items as $itemData) {
                    $pickupItem = PickupItem::create([
                        'pickup_request_id' => $pickup->id,
                        'category_id' => $itemData['category_id'],
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
            }

            DB::commit();

            try {
                $recipient = config('mail.from.address');
                if (!empty($recipient)) {
                    $bookingCode = $pickup->pickup_code;
                    $mailBody = "New corporate enquiry received.\n"
                        . "Booking: {$bookingCode}\n"
                        . "Company: {$request->company_name}\n"
                        . "Contact: {$request->contact_name}\n"
                        . "Mobile: {$request->contact_mobile}\n"
                        . "Email: {$request->contact_email}\n"
                        . "Categories: " . implode(', ', $requestCategories) . "\n"
                        . "Meeting: {$meetingType}\n"
                        . "Scheduled At: {$request->scheduled_at}\n"
                        . "Notes: " . ($request->notes ?? '-');
                    Mail::raw($mailBody, function ($message) use ($recipient, $bookingCode) {
                        $message->to($recipient)->subject("Corporate Enquiry - {$bookingCode}");
                    });
                }
            } catch (\Throwable $mailException) {
                Log::warning('Corporate enquiry email notification failed', [
                    'error' => $mailException->getMessage(),
                ]);
            }

            return $this->successResponse('corporate.created', $pickup->load('items', 'images'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('corporate.create_failed', 500, ['error' => $e->getMessage()]);
        }
    }

    protected function resolveCorporateWarehouse(): ?Warehouse
    {
        // 1) Preferred explicit mapping from app setting.
        $configuredId = (int) AppSetting::get('corporate_main_warehouse_id', 0);
        if ($configuredId > 0) {
            $configured = Warehouse::where('status', true)
                ->where('accepts_corporate', true)
                ->find($configuredId);
            if ($configured) {
                return $configured;
            }
        }

        // 2) Heuristic fallback by code/name.
        $fallback = Warehouse::where('status', true)
            ->where('accepts_corporate', true)
            ->where(function ($q) {
                $q->whereRaw('LOWER(code) = ?', ['main'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%main warehouse%']);
            })
            ->orderBy('id')
            ->first();
        if ($fallback) {
            return $fallback;
        }

        // 3) Final fallback: first active warehouse.
        return Warehouse::where('status', true)
            ->where('accepts_corporate', true)
            ->orderBy('id')
            ->first();
    }

    #[OA\Get(
        path: "/api/corporate-bookings",
        operationId: "getCorporateBookings",
        tags: ["Corporate"],
        summary: "List corporate pickup requests",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of corporate bookings")
        ]
    )]
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PickupRequest::where('request_type', 'corporate')
            ->with(['items', 'images'])
            ->orderBy('created_at', 'desc');

        if ($user->hasRole('customer')) {
            $query->where('customer_id', $user->id);
        }

        if ($request->has('status')) {
            $query->whereIn('status', explode(',', $request->status));
        }

        $requests = $query->paginate(20);
        return $this->paginatedResponse('corporate.fetched', $requests);
    }

    protected function getEnabledCorporateTypes(): Collection
    {
        return CategoryType::with([
            'categories' => function ($query) {
                $query->where('status', true)
                    ->whereNull('parent_id');

                if (Schema::hasColumn($query->getModel()->getTable(), 'sort_order')) {
                    $query->orderBy('sort_order');
                }

                $query->orderBy('id');
            },
        ])
            ->where('status', true)
            ->where('show_in_corporate_booking', true)
            ->orderBy('id')
            ->get();
    }
}
