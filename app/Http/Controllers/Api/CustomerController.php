<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PickupRequest;
use App\Models\PricingRule;
use App\Models\AppSetting;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all categories (parent categories).
     */
    public function getCategories()
    {
        $categories = Category::whereNull('parent_id')
            ->where('status', true)
            ->with([
                'children' => function ($query) {
                    $query->where('status', true);
                }
            ])
            ->get();

        return $this->successResponse('customer.categories_fetched', $categories);
    }

    /**
     * Get specific category details with attributes.
     */
    public function getCategoryDetails($id)
    {
        $category = Category::where('id', $id)
            ->where('status', true)
            ->with(['attributes.options', 'children']) // Load attributes and their options
            ->first();

        if (!$category) {
            return $this->errorResponse('customer.category_not_found', 404);
        }

        return $this->successResponse('customer.category_details_fetched', $category);
    }

    /**
     * Calculate Estimated Price.
     * 
     * Request:
     * {
     *   "category_id": 1,
     *   "attributes": {
     *      "attribute_id_1": "option_id_x",
     *      "attribute_id_2": "value_y" 
     *   },
     *   "weight": 10 (optional, for metal)
     *   "quantity": 2 (optional, for electronics)
     * }
     */
    public function estimatePrice(Request $request)
    {
        // Validation (simplified for MVP)
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'weight' => 'nullable|numeric|min:0.1',
            'quantity' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $price = 0;

        // Fetch Base Price
        // Logic: Find pricing rule for this category.
        // If it's attribute based (e.g. Condition: Working), find that specific rule.

        // This is a simplified logic. In real app, we need to match attributes to detailed pricing rules.
        // For MVP, let's assume simple logic: Base Price * Quantity (or Weight)

        // Check if specific attribute option based price exists
        $attributeOptionId = null;
        if ($request->has('attributes')) {
            // Logic to extract relevant attribute option for pricing (e.g. Condition)
            // For now, let's assume the first attribute is the pricing driver if exists.
        }

        $pricingRule = PricingRule::where('category_id', $request->category_id)
            // ->where('attribute_option_id', $attributeOptionId) // Add this later
            ->first();

        if ($pricingRule) {
            if ($request->weight > 0) {
                $price = $pricingRule->base_price * $request->weight;
            } elseif ($request->quantity > 0) {
                $price = $pricingRule->base_price * $request->quantity;
            }
        }

        return $this->successResponse('customer.price_estimated', ['estimated_amount' => $price, 'currency' => 'INR']);
    }

    /**
     * Create Pickup Request.
     */
    /**
     * Create Pickup Request.
     */
    public function createPickupRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'scheduled_at' => 'required|date|after:now',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.weight' => 'nullable|numeric|min:0.1',
            // 'items.*.attributes' => 'nullable|array', // JSON
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Calculate Total Estimated Amount
        $totalEstimated = 0;
        foreach ($request->items as $item) {
            // Re-use estimation logic or simplified for MVP
            $rule = PricingRule::where('category_id', $item['category_id'])->first();
            if ($rule) {
                if (isset($item['weight']) && $item['weight'] > 0) {
                    $totalEstimated += $rule->base_price * $item['weight'];
                } elseif (isset($item['quantity']) && $item['quantity'] > 0) {
                    $totalEstimated += $rule->base_price * $item['quantity'];
                }
            }
        }

        $minimumFreePickupAmount = (float) AppSetting::get('minimum_free_pickup_amount', 1500);
        $lowValueShippingCharge = (float) AppSetting::get('low_value_shipping_charge', 100);
        $shippingCharge = $totalEstimated < $minimumFreePickupAmount ? $lowValueShippingCharge : 0.0;
        $finalEstimatedAmount = max(0, $totalEstimated - $shippingCharge);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $pickupRequest = PickupRequest::create([
                'customer_id' => $request->user()->id,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'scheduled_at' => $request->scheduled_at,
                'status' => 'pending',
                'estimated_amount' => $finalEstimatedAmount,
                'metadata' => [
                    'pricing_breakdown' => [
                        'subtotal_amount' => round($totalEstimated, 2),
                        'minimum_free_pickup_amount' => round($minimumFreePickupAmount, 2),
                        'shipping_charge' => round($shippingCharge, 2),
                        'final_estimated_amount' => round($finalEstimatedAmount, 2),
                    ],
                ],
            ]);

            foreach ($request->items as $item) {
                \App\Models\PickupItem::create([
                    'pickup_request_id' => $pickupRequest->id,
                    'category_id' => $item['category_id'],
                    'quantity' => $item['quantity'] ?? 0,
                    'weight' => $item['weight'] ?? 0,
                    'attributes' => $item['attributes'] ?? null,
                    'approx_price' => 0, // Calculated later or simplified
                ]);
            }

            // Handle Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('pickup_images', 'public');
                    \App\Models\PickupImage::create([
                        'pickup_request_id' => $pickupRequest->id,
                        'image_path' => $path,
                        'type' => 'pickup',
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return $this->successResponse('customer.pickup_created', $pickupRequest->load('items', 'images'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }
}
