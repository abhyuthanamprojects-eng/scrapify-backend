<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PickupRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pickupBoy = $request->user();
        
        // Calculate distance if both lat/long available
        $distanceKm = null;
        if ($pickupBoy && $pickupBoy->latitude && $pickupBoy->longitude && $this->latitude && $this->longitude) {
            $distanceKm = $this->calculateDistance($pickupBoy->latitude, $pickupBoy->longitude, $this->latitude, $this->longitude);
        }

        $itemsList = $this->whenLoaded('items', function() {
            return $this->items->map(function($item) {
                return [
                    'pickup_item_id' => $item->id,
                    'item_id' => $item->item_id,
                    'category_name' => $item->category ? $item->category->getTranslatedName() : ($item->product_name ?: 'Item'),
                    'weight_kg' => $item->weight,
                    'quantity' => $item->quantity,
                    'condition' => $item->condition,
                    'rate_per_kg' => $item->price_per_unit,
                    'rate_per_unit' => $item->price_per_unit,
                    'carbon_per_unit' => $item->carbon_per_unit,
                    'total_carbon_saved' => $item->total_carbon_saved,
                    'total_price' => $item->total_price,
                    'remarks' => $item->remarks,
                    'image_path' => $item->image_path,
                    'image_url' => $item->image_path ? asset('storage/' . ltrim($item->image_path, '/')) : null,
                ];
            });
        });

        $attributesList = $this->whenLoaded('requestAttributes', function () {
            return $this->requestAttributes->map(function ($row) {
                $attributeName = $row->attribute?->name;
                $value = $row->value;

                return [
                    'attribute_id' => $row->attribute_id,
                    'attribute_name' => is_array($attributeName) ? ($attributeName['en'] ?? reset($attributeName)) : $attributeName,
                    'value' => is_array($value) ? ($value['en'] ?? reset($value)) : $value,
                    'raw_value' => $row->value,
                ];
            })->values();
        });

        $imagesList = $this->whenLoaded('images', function () {
            return $this->images->map(fn ($image) => [
                'id' => $image->id,
                'type' => $image->type,
                'image_path' => $image->image_path,
                'url' => $image->url,
                'latitude' => $image->latitude,
                'longitude' => $image->longitude,
                'remarks' => $image->remarks,
            ])->values();
        });

        $itemsSummary = $this->whenLoaded('items', function() {
            return $this->items->map(function($item) {
                return $item->category ? $item->category->getTranslatedName() : ($item->product_name ?: 'Item');
            })->unique()->values()->implode(', ');
        });

        $estimatedWeight = $this->whenLoaded('items', function() {
            return $this->items->reduce(function($carry, $item) {
                return $carry + ($item->weight ?? 0);
            }, 0);
        });

        return [
            'pickup_id' => $this->id,
            'order_code' => $this->pickup_code,
            'request_type' => $this->request_type,
            'metadata' => $this->metadata,
            'customer_name' => $this->customer_name ?: ($this->customer ? $this->customer->name : null),
            'customer_phone' => $this->customer_phone ?: ($this->customer ? $this->customer->phone : null),
            'customer_image' => $this->customer ? $this->customer->profile_photo_path : null,
            'address' => $this->address ?: ($this->address()->first() ? $this->address()->first()->address_line_1 : null),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_km' => $distanceKm !== null ? round($distanceKm, 2) : null,
            'scheduled_at' => $this->scheduled_at ? $this->scheduled_at->format('Y-m-d H:i:s') : null,
            'items' => $itemsList,
            'booking_attributes' => $attributesList,
            'items_summary' => $itemsSummary,
            'estimated_weight_kg' => $estimatedWeight,
            'total_carbon_saved' => $this->whenLoaded('items', function () {
                return round($this->items->sum('total_carbon_saved'), 3);
            }),
            'estimated_amount' => $this->estimated_amount,
            'final_amount' => $this->final_amount,
            'price_summary' => [
                'subtotal_amount' => data_get($this->metadata, 'pricing_breakdown.subtotal_amount'),
                'minimum_free_pickup_amount' => data_get($this->metadata, 'pricing_breakdown.minimum_free_pickup_amount'),
                'shipping_charge' => data_get($this->metadata, 'pricing_breakdown.shipping_charge'),
                'final_estimated_amount' => data_get($this->metadata, 'pricing_breakdown.final_estimated_amount', $this->estimated_amount),
                'final_amount' => $this->final_amount,
                'is_price_locked' => $this->price_locked_at !== null,
            ],
            'assigned_pickup_boy' => $this->whenLoaded('assignment', function () {
                $pickupBoy = $this->assignment?->pickupBoy;
                if (!$pickupBoy) {
                    return null;
                }

                if ($pickupBoy->hasRole('pickup_boy')) {
                    $pickupBoy->ensureEmployeeId();
                }

                return [
                    'id' => $pickupBoy->id,
                    'name' => $pickupBoy->name,
                    'phone' => $pickupBoy->phone,
                    'employee_id' => $pickupBoy->employee_id,
                    'vehicle_number' => $pickupBoy->vehicle_number,
                    'profile_photo' => $pickupBoy->profile_photo_path,
                    'profile_photo_url' => $pickupBoy->profile_photo_url,
                ];
            }),
            'status' => $this->status, // pending, assigned, accepted, on_the_way, verifying, picked_up, completed, rejected, cancelled
            'reschedule_allowed' => in_array($this->status, ['pending', 'assigned', 'accepted']),
            'notes' => $this->notes,
            'images' => $imagesList,
            'status_timeline' => $this->whenLoaded('statusLogs'),
            'final_payout_amount' => $this->final_amount,
            'verification_required' => \App\Models\AppSetting::get('verification_required', true),
        ];
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
