<?php

namespace App\Services;

use App\Models\AttributeOption;
use App\Models\PricingRule;
use App\Models\PricingVariantRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomeAppliancePricingService
{
    public function estimateWithMeta(int $categoryId, array $optionIds): array
    {
        $baseRule = PricingRule::where('category_id', $categoryId)
            ->whereNull('attribute_option_id')
            ->where('status', true)
            ->first();

        $calculation = $this->calculate($categoryId, $optionIds);

        return [
            'estimated_price' => $calculation['estimated_price'],
            'carbon_per_unit' => $calculation['carbon_per_unit'],
            'pricing_type' => $baseRule?->pricing_type ?? 'per_piece',
            'variant_rule' => $calculation['variant_rule'],
        ];
    }

    public function estimate(int $categoryId, array $optionIds): float
    {
        return $this->calculate($categoryId, $optionIds)['estimated_price'];
    }

    private function calculate(int $categoryId, array $optionIds): array
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
        $baseCarbonPerUnit = $baseRule && $baseRule->carbon_per_unit !== null
            ? (float) $baseRule->carbon_per_unit
            : 0.0;

        $options = AttributeOption::with('attribute')
            ->whereIn('id', $optionIds)
            ->get();

        $variantRule = $this->matchVariantRule($categoryId, $options);
        $variantOptionLabels = $variantRule
            ? collect($variantRule->option_values ?? [])->map(fn($value) => $this->normalizeOptionLabel($value))->all()
            : [];
        $calculationBase = $variantRule ? (float) $variantRule->base_price : $basePrice;

        if (empty($optionIds)) {
            return [
                'estimated_price' => round($basePrice, 2),
                'carbon_per_unit' => round($baseCarbonPerUnit, 3),
                'variant_rule' => null,
            ];
        }

        $matchedRules = PricingRule::where('category_id', $categoryId)
            ->whereIn('attribute_option_id', $optionIds)
            ->where('status', true)
            ->get(['attribute_option_id', 'base_price', 'adjustment_type', 'adjustment_value', 'carbon_per_unit']);

        if ($matchedRules->isNotEmpty()) {
            $optionLabelsById = $options
                ->mapWithKeys(fn(AttributeOption $option) => [
                    (int) $option->id => $this->normalizeOptionLabel($option->value['en'] ?? $option->value ?? ''),
                ]);

            $sumDeltas = $matchedRules->sum(function ($rule) use ($basePrice, $calculationBase, $variantOptionLabels, $optionLabelsById) {
                $optionLabel = $optionLabelsById->get((int) $rule->attribute_option_id);
                if ($optionLabel && in_array($optionLabel, $variantOptionLabels, true)) {
                    return 0;
                }

                $type = $rule->adjustment_type ?? 'fixed';
                $adjustmentValue = $rule->adjustment_value;

                if ($adjustmentValue !== null) {
                    if ($type === 'percentage') {
                        return $calculationBase * ((float) $adjustmentValue / 100);
                    }
                    return (float) $adjustmentValue;
                }

                // Legacy fallback for old rows without adjustment columns.
                return (float) $rule->base_price - $basePrice;
            });

            $price = $calculationBase + (float) $sumDeltas;
            $carbonOverride = $matchedRules
                ->map(fn($rule) => $rule->carbon_per_unit !== null ? (float) $rule->carbon_per_unit : null)
                ->first(fn($value) => $value !== null);
            return [
                'estimated_price' => round(max(0, $price), 2),
                'carbon_per_unit' => round($carbonOverride ?? $baseCarbonPerUnit, 3),
                'variant_rule' => $variantRule ? [
                    'id' => $variantRule->id,
                    'title' => $variantRule->title,
                    'base_price' => (float) $variantRule->base_price,
                    'option_values' => $variantRule->option_values ?? [],
                ] : null,
            ];
        }

        // Fallback strategy when option-specific pricing rules are not configured:
        // derive deterministic deltas from selected option labels.
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

        return [
            'estimated_price' => round(max(0, $calculationBase + $fallbackDelta), 2),
            'carbon_per_unit' => round($baseCarbonPerUnit, 3),
            'variant_rule' => $variantRule ? [
                'id' => $variantRule->id,
                'title' => $variantRule->title,
                'base_price' => (float) $variantRule->base_price,
                'option_values' => $variantRule->option_values ?? [],
            ] : null,
        ];
    }

    private function matchVariantRule(int $categoryId, Collection $selectedOptions): ?PricingVariantRule
    {
        if (!Schema::hasTable('pricing_variant_rules')) {
            return null;
        }

        $selectedLabels = $selectedOptions
            ->map(fn(AttributeOption $option) => $this->normalizeOptionLabel($option->value['en'] ?? $option->value ?? ''))
            ->filter()
            ->unique()
            ->values();

        if ($selectedLabels->isEmpty()) {
            return null;
        }

        return PricingVariantRule::where('category_id', $categoryId)
            ->where('status', true)
            ->get()
            ->sortByDesc(fn(PricingVariantRule $rule) => count($rule->option_values ?? []))
            ->first(function (PricingVariantRule $rule) use ($selectedLabels) {
                $requiredLabels = collect($rule->option_values ?? [])
                    ->map(fn($value) => $this->normalizeOptionLabel($value))
                    ->filter()
                    ->values();

                if ($requiredLabels->isEmpty()) {
                    return false;
                }

                return $requiredLabels->every(fn($label) => $selectedLabels->contains($label));
            });
    }

    private function normalizeOptionLabel(mixed $value): string
    {
        return trim(mb_strtolower((string) $value));
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
