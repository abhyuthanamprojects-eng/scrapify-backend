<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PricingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarbonFootprintSeeder extends Seeder
{
    public function run(): void
    {
        Category::with(['categoryType', 'pricingRules'])
            ->whereHas('pricingRules', fn ($query) => $query->whereNull('attribute_option_id'))
            ->chunkById(100, function ($categories) {
                foreach ($categories as $category) {
                    $baseRule = $category->pricingRules->firstWhere('attribute_option_id', null);
                    if (!$baseRule) {
                        continue;
                    }

                    $currentValue = $baseRule->carbon_per_unit;
                    if ($currentValue !== null && (float) $currentValue > 0) {
                        continue;
                    }

                    $carbonPerUnit = $this->resolveCarbonPerUnit(
                        $category->name['en'] ?? (string) $category->name,
                        $category->categoryType->name['en'] ?? (string) ($category->categoryType->name ?? ''),
                        (string) ($baseRule->pricing_type ?? 'per_piece')
                    );

                    PricingRule::whereKey($baseRule->id)->update([
                        'carbon_per_unit' => $carbonPerUnit,
                    ]);
                }
            });
    }

    private function resolveCarbonPerUnit(string $itemName, string $categoryTypeName, string $pricingType): float
    {
        $normalizedItem = Str::lower(trim($itemName));
        $normalizedType = Str::lower(trim($categoryTypeName));

        $explicitMap = [
            'air conditioner' => 85.0,
            'washing machine' => 32.0,
            'television' => 18.0,
            'microwave' => 14.0,
            'refrigerator' => 55.0,
            'mixer grinder' => 7.0,
            'kitchen chimney' => 12.0,
            'water purifier' => 9.0,
            'mobile phone' => 12.0,
            'laptop' => 28.0,
            'cables & wires' => 6.0,
            'cpu cabinet' => 20.0,
            'desktop computer' => 26.0,
            'crt monitor' => 16.0,
            'lcd monitor' => 9.0,
            'led monitor' => 10.0,
            'mouse' => 0.3,
            'keyboard' => 0.8,
            'motherboard' => 6.0,
            'hard disk drive' => 4.0,
            'server' => 45.0,
            'ram' => 1.5,
            'printer' => 10.0,
            'scanner' => 8.0,
            'tablet' => 10.0,
            'charger' => 0.5,
            'laptop adapter' => 0.8,
            'mobile adaptor' => 0.3,
            'power bank' => 2.5,
            'earbuds/earphone' => 0.4,
            'headphones' => 1.1,
            'induction cooktop' => 8.0,
            'ups 600 va with battery' => 18.0,
            'ups 600 va without battery' => 7.0,
            'inverter with battery' => 35.0,
            'inverter without battery' => 14.0,
            'geyser' => 11.0,
            'ceiling fan / wall mounted fan' => 6.0,
            'table fan / stand fan' => 5.0,
            'air cooler' => 10.0,
            'ms scrap' => 1.8,
            'cast iron scrap' => 1.7,
            'heavy melting scrap' => 1.8,
            'iron rod / saria scrap' => 1.9,
            'old steel pipes & plates' => 2.0,
            'machinery iron parts' => 1.9,
            'copper wire' => 3.8,
            'copper' => 4.2,
            'bras' => 2.1,
            'aluminium scrap' => 1.7,
            'lead scrap' => 1.3,
            'zinc scrap' => 1.1,
            'nickel scrap' => 3.0,
            'cnc cutting scrap' => 1.8,
            'punching scrap' => 1.8,
            'metal turning (boring scrap)' => 1.6,
            'fabrication waste' => 1.7,
            'iron nails' => 1.8,
            'battery' => 2.5,
            'water bottles' => 1.6,
            'soft drink bottles' => 1.6,
            'transparent oil bottles' => 1.7,
            'detergent bottles' => 1.9,
            'chemical cans' => 1.9,
            'plastic drums' => 2.2,
            'pipes' => 1.8,
            'wire insulations' => 1.3,
            'flex sheets' => 1.0,
            'carry bags' => 0.9,
            'packaging films' => 0.9,
            'stretch wrap' => 1.0,
            'plastic crates' => 2.1,
            'plastic chairs' => 2.0,
            'battery boxes' => 2.1,
            'thermocol' => 0.6,
            'disposable cups' => 0.7,
            'foam packaging' => 0.7,
            'newspaper' => 1.3,
            'cardboard' => 0.9,
            'plastic bottles' => 1.6,
            'glass bottles' => 0.7,
            'white record paper' => 1.4,
            'office paper scrap' => 1.4,
            'mixed paper' => 1.0,
            'books scrap' => 1.2,
            'notebook scrap' => 1.1,
            'brown corrugated carton scrap' => 0.9,
            'duplex board carton scrap' => 1.0,
            'corrugated sheet / punching waste' => 0.9,
            'wooden chair' => 18.0,
            'steel cupboard' => 42.0,
            'study table' => 20.0,
            'sofa set' => 28.0,
            'bed' => 55.0,
            'dressing table' => 24.0,
            'dining table' => 38.0,
            'work stations' => 44.0,
            'reception table' => 60.0,
            'boss chair' => 26.0,
            'settee sofa' => 32.0,
            'lithium-ion battery' => 7.0,
            'inverter battery' => 3.5,
            'used oil' => 0.7,
            'lead' => 1.4,
            'cfl bulb' => 0.1,
            'tube light' => 0.2,
            'bulb' => 0.1,
            'scooty' => 210.0,
            'bike' => 320.0,
            'car' => 1800.0,
            'tata ace' => 2200.0,
            'pick bolero' => 2500.0,
            'tata 407' => 4200.0,
            'bus' => 9000.0,
            'truck' => 12000.0,
        ];

        if (isset($explicitMap[$normalizedItem])) {
            return $explicitMap[$normalizedItem];
        }

        if ($pricingType === 'per_kg') {
            return $this->resolvePerKgFallback($normalizedItem, $normalizedType);
        }

        return $this->resolvePerPieceFallback($normalizedItem, $normalizedType);
    }

    private function resolvePerKgFallback(string $itemName, string $categoryTypeName): float
    {
        if (str_contains($itemName, 'copper')) {
            return 4.0;
        }
        if (str_contains($itemName, 'aluminium')) {
            return 1.7;
        }
        if (str_contains($itemName, 'lead')) {
            return 1.3;
        }
        if (str_contains($itemName, 'battery')) {
            return 2.8;
        }
        if (str_contains($itemName, 'paper') || str_contains($itemName, 'cardboard') || str_contains($itemName, 'carton') || str_contains($categoryTypeName, 'paper')) {
            return 1.1;
        }
        if (str_contains($itemName, 'plastic') || str_contains($itemName, 'bottle') || str_contains($itemName, 'film') || str_contains($categoryTypeName, 'plastic')) {
            return 1.5;
        }
        if (str_contains($itemName, 'glass')) {
            return 0.7;
        }
        if (str_contains($itemName, 'iron') || str_contains($itemName, 'steel') || str_contains($itemName, 'metal') || str_contains($categoryTypeName, 'metal')) {
            return 1.8;
        }
        if (str_contains($categoryTypeName, 'hazardous')) {
            return 1.2;
        }

        return 1.0;
    }

    private function resolvePerPieceFallback(string $itemName, string $categoryTypeName): float
    {
        if (str_contains($categoryTypeName, 'vehicle')) {
            return 500.0;
        }
        if (str_contains($categoryTypeName, 'old furniture') || str_contains($itemName, 'chair') || str_contains($itemName, 'table') || str_contains($itemName, 'bed') || str_contains($itemName, 'sofa')) {
            return 25.0;
        }
        if (str_contains($categoryTypeName, 'e-waste') || str_contains($itemName, 'monitor') || str_contains($itemName, 'computer') || str_contains($itemName, 'printer')) {
            return 8.0;
        }
        if (str_contains($categoryTypeName, 'hazardous')) {
            return 0.2;
        }

        return 5.0;
    }
}
