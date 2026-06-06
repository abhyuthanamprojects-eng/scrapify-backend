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
                    'total_price' => $item->total_price,
                ];
            });
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
            'customer_name' => $this->customer_name ?: ($this->customer ? $this->customer->name : null),
            'customer_phone' => $this->customer_phone ?: ($this->customer ? $this->customer->phone : null),
            'customer_image' => $this->customer ? $this->customer->profile_photo_path : null,
            'address' => $this->address ?: ($this->address()->first() ? $this->address()->first()->address_line_1 : null),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_km' => $distanceKm !== null ? round($distanceKm, 2) : null,
            'scheduled_at' => $this->scheduled_at ? $this->scheduled_at->format('Y-m-d H:i:s') : null,
            'items' => $itemsList,
            'items_summary' => $itemsSummary,
            'estimated_weight_kg' => $estimatedWeight,
            'status' => $this->status, // pending, assigned, accepted, on_the_way, verifying, picked_up, completed, rejected, cancelled
            'reschedule_allowed' => in_array($this->status, ['pending', 'assigned', 'accepted']),
            'notes' => $this->notes,
            'images' => $this->whenLoaded('images'),
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
