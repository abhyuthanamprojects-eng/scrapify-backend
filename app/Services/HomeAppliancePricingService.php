<?php

namespace App\Services;

use App\Models\AttributeOption;
use App\Models\PricingRule;

class HomeAppliancePricingService
{
    public function estimateWithMeta(int $categoryId, array $optionIds): array
    {
        $baseRule = PricingRule::where('category_id', $categoryId)
            ->whereNull('attribute_option_id')
            ->where('status', true)
            ->first();

        return [
            'estimated_price' => $this->estimate($categoryId, $optionIds),
            'pricing_type' => $baseRule?->pricing_type ?? 'per_piece',
        ];
    }

    public function estimate(int $categoryId, array $optionIds): float
    {
        $optionIds = collect($optionIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $baseRule = PricingRule::where('category_id', $categoryId)
            ->whereNull('attribute_option_id')
            ->where('status', true)
            ->first();

        $basePrice = $baseRule ? (float) $baseRule->base_price : 0.0;

        if (empty($optionIds)) {
            return round($basePrice, 2);
        }

        $matchedRules = PricingRule::where('category_id', $categoryId)
            ->whereIn('attribute_option_id', $optionIds)
            ->where('status', true)
            ->get(['attribute_option_id', 'base_price', 'adjustment_type', 'adjustment_value']);

        if ($matchedRules->isNotEmpty()) {
            $sumDeltas = $matchedRules->sum(function ($rule) use ($basePrice) {
                $type = $rule->adjustment_type ?? 'fixed';
                $adjustmentValue = $rule->adjustment_value;

                if ($adjustmentValue !== null) {
                    if ($type === 'percentage') {
                        return $basePrice * ((float) $adjustmentValue / 100);
                    }
                    return (float) $adjustmentValue;
                }

                // Legacy fallback for old rows without adjustment columns.
                return (float) $rule->base_price - $basePrice;
            });

            $price = $basePrice + (float) $sumDeltas;
            return round(max(0, $price), 2);
        }

        // Fallback strategy when option-specific pricing rules are not configured:
        // derive deterministic deltas from selected option labels.
        $options = AttributeOption::with('attribute')
            ->whereIn('id', $optionIds)
            ->get();

        $fallbackDelta = 0.0;
        foreach ($options as $option) {
            $optionValue = strtolower((string) ($option->value['en'] ?? $option->value ?? ''));
            $attributeName = strtolower((string) ($option->attribute->name['en'] ?? $option->attribute->name ?? ''));
            $attributeSlug = strtolower((string) ($option->attribute->slug ?? ''));

            if (str_contains($attributeSlug, 'material') || str_contains($attributeName, 'material')) {
                $fallbackDelta += $this->materialDelta($optionValue);
                continue;
            }

            if (str_contains($attributeSlug, 'size') || str_contains($attributeName, 'size')) {
                $fallbackDelta += $this->sizeDelta($optionValue);
                continue;
            }

            if (str_contains($attributeSlug, 'condition') || str_contains($attributeName, 'condition')) {
                $fallbackDelta += $this->conditionDelta($optionValue);
                continue;
            }
        }

        return round(max(0, $basePrice + $fallbackDelta), 2);
    }

    private function materialDelta(string $value): float
    {
        return match (true) {
            str_contains($value, 'metal') => 300,
            str_contains($value, 'mixed') => 100,
            str_contains($value, 'plastic') => -150,
            default => 0,
        };
    }

    private function sizeDelta(string $value): float
    {
        return match (true) {
            str_contains($value, 'small') => -250,
            str_contains($value, 'medium') => 0,
            str_contains($value, 'large') => 450,
            str_contains($value, 'bulk') || str_contains($value, 'xl') => 800,
            default => 0,
        };
    }

    private function conditionDelta(string $value): float
    {
        return match (true) {
            str_contains($value, 'working') => 350,
            str_contains($value, 'refurbished') => 200,
            str_contains($value, 'scrap') => -200,
            str_contains($value, 'non-working') => -300,
            default => 0,
        };
    }
}
