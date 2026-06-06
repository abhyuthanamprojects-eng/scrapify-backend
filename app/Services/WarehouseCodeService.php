<?php

namespace App\Services;

use App\Models\City;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseCodeService
{
    /**
     * Generate WH-{STATE}-{CITY}-{SEQ}.
     * Sequence persists across deletes (uses max found code prefix).
     */
    public function generate(City $city): string
    {
        $stateCode = $this->normalize($city->state?->code ?: $city->state?->name ?: 'XX', 2);
        $cityCode  = $this->normalize($city->code ?: $city->name, 3);
        $prefix    = "WH-{$stateCode}-{$cityCode}-";

        return DB::transaction(function () use ($prefix) {
            // include soft-deleted to avoid reuse
            $last = Warehouse::withoutGlobalScopes()
                ->where('code', 'like', $prefix . '%')
                ->orderByDesc('code')
                ->lockForUpdate()
                ->value('code');

            $next = 1;
            if ($last) {
                $tail = (int) substr($last, strlen($prefix));
                $next = $tail + 1;
            }

            return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        });
    }

    private function normalize(string $value, int $len): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $value));
        if ($clean === '') $clean = strtoupper(Str::random($len));
        return substr(str_pad($clean, $len, 'X'), 0, $len);
    }
}
