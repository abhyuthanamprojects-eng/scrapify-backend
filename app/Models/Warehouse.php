<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory, \App\Traits\BelongsToPartner;

    protected $guarded = [];
    protected $fillable = ['channel_partner_id', 'name', 'address', 'latitude', 'longitude', 'manager_id', 'status', 'accepts_corporate', 'accepts_donation', 'service_pincodes', 'city_id', 'area', 'zone', 'service_types', 'code', 'capacity'];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'status' => 'boolean',
        'accepts_corporate' => 'boolean',
        'accepts_donation' => 'boolean',
        'service_types' => 'json',
        'service_pincodes' => 'array',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function channelPartner()
    {
        return $this->belongsTo(ChannelPartner::class);
    }

    /**
     * Legacy single-warehouse mapping via users.warehouse_id.
     */
    public function legacyPickupBoys()
    {
        return $this->hasMany(User::class, 'warehouse_id');
    }

    /**
     * Many-to-many pickup-boy mapping via pivot.
     */
    public function pickupBoys()
    {
        return $this->belongsToMany(User::class, 'pickup_boy_warehouse', 'warehouse_id', 'pickup_boy_id')
            ->withPivot(['status', 'created_by'])
            ->withTimestamps();
    }

    public function activePickupBoys()
    {
        return $this->pickupBoys()->wherePivot('status', 'active');
    }

    public function orders()
    {
        return $this->hasMany(PickupRequest::class, 'warehouse_id');
    }

    public static function normalizePincode(?string $pincode): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $pincode);

        if (strlen($digits) < 6) {
            return null;
        }

        return substr($digits, 0, 6);
    }

    public static function normalizePincodeList(mixed $pincodes, int $limit = 10): array
    {
        $values = is_string($pincodes)
            ? preg_split('/[\s,]+/', trim($pincodes)) ?: []
            : (is_array($pincodes) ? $pincodes : []);

        return collect($values)
            ->map(fn ($pincode) => self::normalizePincode(is_string($pincode) ? $pincode : (string) $pincode))
            ->filter()
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }

    public static function findByPincode(?string $pincode, ?string $serviceType = null): ?self
    {
        return self::findBestByPincode($pincode, null, null, $serviceType);
    }

    public static function findBestByPincode(?string $pincode, ?float $latitude = null, ?float $longitude = null, ?string $serviceType = null): ?self
    {
        $normalized = self::normalizePincode($pincode);

        if (!$normalized) {
            return null;
        }

        $query = self::withoutGlobalScopes()
            ->where('status', true)
            ->whereJsonContains('service_pincodes', $normalized);

        if ($serviceType === 'donation') {
            $query->where('accepts_donation', true);
        }

        $warehouses = $query->get();

        if ($warehouses->isEmpty()) {
            return null;
        }

        if ($latitude === null || $longitude === null) {
            return $warehouses->first();
        }

        return $warehouses
            ->sortBy(fn (self $warehouse) => self::calculateDistanceKm(
                $latitude,
                $longitude,
                (float) ($warehouse->latitude ?? 0),
                (float) ($warehouse->longitude ?? 0)
            ))
            ->first();
    }

    public static function matchingByPincode(?string $pincode, ?string $serviceType = null)
    {
        $normalized = self::normalizePincode($pincode);

        if (!$normalized) {
            return collect();
        }

        $query = self::withoutGlobalScopes()
            ->where('status', true)
            ->whereJsonContains('service_pincodes', $normalized);

        if ($serviceType === 'donation') {
            $query->where('accepts_donation', true);
        }

        return $query->orderBy('id')->get();
    }

    public static function duplicateServicePincodes(array $pincodes, ?int $ignoreWarehouseId = null): array
    {
        $duplicates = [];

        foreach (self::normalizePincodeList($pincodes, count($pincodes) ?: 1) as $pincode) {
            $query = self::withoutGlobalScopes()
                ->whereJsonContains('service_pincodes', $pincode);

            if ($ignoreWarehouseId) {
                $query->whereKeyNot($ignoreWarehouseId);
            }

            $warehouse = $query->first(['id', 'name']);

            if ($warehouse) {
                $duplicates[$pincode] = [
                    'warehouse_id' => $warehouse->id,
                    'warehouse_name' => $warehouse->name,
                ];
            }
        }

        return $duplicates;
    }

    private static function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
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
}
