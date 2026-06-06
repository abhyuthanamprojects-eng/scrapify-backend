<?php

namespace App\Services;

use App\Models\City;
use App\Models\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * Reverse-geocode coordinates via Google Maps Geocoding API.
     * Maps result to existing City/State; auto-fills zone from city.default_zone.
     *
     * @return array{
     *   formatted_address: string|null,
     *   city: ?City,
     *   state: ?State,
     *   zone: string|null,
     *   raw_city: string|null,
     *   raw_state: string|null,
     *   pincode: string|null
     * }
     */
    public function reverseGeocode(float $lat, float $lng): array
    {
        $apiKey = config('services.google_maps.key', env('GOOGLE_MAPS_API_KEY'));

        $resp = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng' => "{$lat},{$lng}",
            'key'    => $apiKey,
        ]);

        $data = $resp->json();
        if (($data['status'] ?? null) !== 'OK' || empty($data['results'])) {
            Log::warning('Reverse geocode failed', ['status' => $data['status'] ?? null, 'lat' => $lat, 'lng' => $lng]);
            return $this->empty();
        }

        $result = $data['results'][0];
        $components = collect($result['address_components'] ?? []);

        $cityName  = $this->pickComponent($components, ['locality', 'administrative_area_level_3', 'administrative_area_level_2']);
        $stateName = $this->pickComponent($components, ['administrative_area_level_1']);
        $pincode   = $this->pickComponent($components, ['postal_code']);

        $state = $stateName ? State::whereRaw('LOWER(name) = ?', [strtolower($stateName)])->first() : null;
        $city  = null;
        if ($cityName) {
            $cityQuery = City::whereRaw('LOWER(name) = ?', [strtolower($cityName)]);
            if ($state) $cityQuery->where('state_id', $state->id);
            $city = $cityQuery->first();
        }

        return [
            'formatted_address' => $result['formatted_address'] ?? null,
            'city'      => $city,
            'state'     => $state,
            'zone'      => $city?->default_zone,
            'raw_city'  => $cityName,
            'raw_state' => $stateName,
            'pincode'   => $pincode,
        ];
    }

    private function pickComponent($components, array $types): ?string
    {
        foreach ($types as $type) {
            $hit = $components->first(fn ($c) => in_array($type, $c['types'] ?? []));
            if ($hit) return $hit['long_name'] ?? null;
        }
        return null;
    }

    private function empty(): array
    {
        return [
            'formatted_address' => null,
            'city' => null,
            'state' => null,
            'zone' => null,
            'raw_city' => null,
            'raw_state' => null,
            'pincode' => null,
        ];
    }
}
