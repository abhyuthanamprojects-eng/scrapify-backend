<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\PricingRule;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with(['pricingRules', 'categoryType'])
            ->whereNull('parent_id');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->category_type_id) {
            $query->where('category_type_id', $request->category_type_id);
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $categories = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_type_id', 'status']),
            'types' => \App\Models\CategoryType::where('status', true)->get(),
        ]);
    }

    public function create()
    {
        $types = \App\Models\CategoryType::where('status', true)->get();
        $attributes = Attribute::with('options')
            ->where('status', true)
            ->get();

        return Inertia::render('Admin/Categories/Form', [
            'category' => null,
            'types' => $types,
            'attributes' => $attributes->map(fn(Attribute $attribute) => [
                'id' => $attribute->id,
                'name' => $attribute->name['en'] ?? $attribute->name,
                'slug' => $attribute->slug,
                'options' => $attribute->options->map(fn($option) => [
                    'id' => $option->id,
                    'value' => $option->value['en'] ?? $option->value,
                ])->values(),
            ])->values(),
            'selectedAttributeIds' => [],
            'optionPricingRules' => [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.hi' => 'nullable|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
            'category_type_id' => 'required|exists:category_types,id',
            'status' => 'boolean',
            'requires_details' => 'boolean',
            'selected_attribute_ids' => 'nullable|array',
            'selected_attribute_ids.*' => 'integer|exists:attributes,id',
            'pricing_type' => 'required|in:per_kg,per_piece,per_capacity',
            'base_price' => 'required|numeric|min:0',
            'carbon_per_unit' => 'nullable|numeric|min:0',
            'option_pricing_rules' => 'nullable|array',
            'option_pricing_rules.*.attribute_option_id' => 'required|exists:attribute_options,id',
            'option_pricing_rules.*.adjustment_percent' => 'required|numeric|min:-100|max:1000',
            'option_pricing_rules.*.carbon_per_unit' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'category_type_id' => $validated['category_type_id'],
            'parent_id' => null,
            'status' => $validated['status'] ?? true,
            'requires_details' => $validated['requires_details'] ?? false,
            'image_path' => $imagePath,
        ]);

        $selectedAttributeIds = collect($validated['selected_attribute_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
        $category->attributes()->sync($selectedAttributeIds->all());

        PricingRule::create([
            'category_id' => $category->id,
            'pricing_type' => $validated['pricing_type'],
            'base_price' => $validated['base_price'],
            'carbon_per_unit' => $validated['carbon_per_unit'] ?? 0,
            'min_quantity' => 1,
        ]);

        $this->upsertOptionPricingRules(
            $category->id,
            $request->input('option_pricing_rules', []),
            $validated['pricing_type'],
            $selectedAttributeIds
        );

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $category->load(['pricingRules', 'attributes.options']);
        $types = \App\Models\CategoryType::where('status', true)->get();
        $attributes = Attribute::with('options')
            ->where('status', true)
            ->get();

        $selectedAttributeIds = $category->attributes->pluck('id')->map(fn($id) => (int) $id)->values();
        $allowedOptionIds = $attributes
            ->whereIn('id', $selectedAttributeIds)
            ->flatMap(fn(Attribute $attribute) => $attribute->options->pluck('id'))
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $optionPricingRules = $category->pricingRules
            ->whereNotNull('attribute_option_id')
            ->filter(fn($rule) => in_array((int) $rule->attribute_option_id, $allowedOptionIds, true))
            ->map(fn($rule) => [
                'attribute_option_id' => $rule->attribute_option_id,
                'adjustment_percent' => $rule->adjustment_value !== null ? (float) $rule->adjustment_value : 0,
                'pricing_type' => $rule->pricing_type,
                'carbon_per_unit' => $rule->carbon_per_unit !== null ? (float) $rule->carbon_per_unit : null,
            ])
            ->values();

        return Inertia::render('Admin/Categories/Form', [
            'category' => $category,
            'types' => $types,
            'attributes' => $attributes
                ->map(fn(Attribute $attribute) => [
                    'id' => $attribute->id,
                    'name' => $attribute->name['en'] ?? $attribute->name,
                    'slug' => $attribute->slug,
                    'options' => $attribute->options->map(fn($option) => [
                        'id' => $option->id,
                        'value' => $option->value['en'] ?? $option->value,
                    ])->values(),
                ])
                ->values(),
            'selectedAttributeIds' => $selectedAttributeIds,
            'optionPricingRules' => $optionPricingRules,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.hi' => 'nullable|string|max:255',
            'slug' => 'required|string|unique:categories,slug,' . $category->id,
            'category_type_id' => 'required|exists:category_types,id',
            'status' => 'boolean',
            'requires_details' => 'boolean',
            'selected_attribute_ids' => 'nullable|array',
            'selected_attribute_ids.*' => 'integer|exists:attributes,id',
            'pricing_type' => 'required|in:per_kg,per_piece,per_capacity',
            'base_price' => 'required|numeric|min:0',
            'carbon_per_unit' => 'nullable|numeric|min:0',
            'option_pricing_rules' => 'nullable|array',
            'option_pricing_rules.*.attribute_option_id' => 'required|exists:attribute_options,id',
            'option_pricing_rules.*.adjustment_percent' => 'required|numeric|min:-100|max:1000',
            'option_pricing_rules.*.carbon_per_unit' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|boolean',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'category_type_id' => $validated['category_type_id'],
            'parent_id' => null,
            'status' => $validated['status'] ?? true,
            'requires_details' => $validated['requires_details'] ?? false,
        ];

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $updateData['image_path'] = $request->file('image')->store('categories', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $updateData['image_path'] = null;
        }

        $category->update($updateData);

        $selectedAttributeIds = collect($validated['selected_attribute_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
        $category->attributes()->sync($selectedAttributeIds->all());

        $category->pricingRules()->updateOrCreate(
            ['category_id' => $category->id, 'attribute_option_id' => null],
            [
                'pricing_type' => $validated['pricing_type'],
                'base_price' => $validated['base_price'],
                'carbon_per_unit' => $validated['carbon_per_unit'] ?? 0,
                'min_quantity' => 1,
            ]
        );
        $this->upsertOptionPricingRules(
            $category->id,
            $request->input('option_pricing_rules', []),
            $validated['pricing_type'],
            $selectedAttributeIds
        );

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function upsertOptionPricingRules(int $categoryId, array $rules, string $defaultPricingType, Collection $selectedAttributeIds): void
    {
        $allowedOptionIds = Attribute::with('options:id,attribute_id')
            ->whereIn('id', $selectedAttributeIds->all())
            ->get()
            ->flatMap(fn(Attribute $attribute) => $attribute->options->pluck('id'))
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $rulesCollection = collect($rules)
            ->filter(function ($row) use ($allowedOptionIds) {
                if (empty($row['attribute_option_id']) || $row['adjustment_percent'] === null || $row['adjustment_percent'] === '') {
                    return false;
                }

                return in_array((int) $row['attribute_option_id'], $allowedOptionIds, true);
            })
            ->map(fn($row) => [
                'attribute_option_id' => (int) $row['attribute_option_id'],
                'adjustment_percent' => (float) $row['adjustment_percent'],
                'pricing_type' => $row['pricing_type'] ?? $defaultPricingType,
                'carbon_per_unit' => isset($row['carbon_per_unit']) && $row['carbon_per_unit'] !== ''
                    ? (float) $row['carbon_per_unit']
                    : null,
            ])
            ->unique('attribute_option_id')
            ->values();

        $allowedOptionIds = $rulesCollection->pluck('attribute_option_id')->all();

        // Remove old option-level rules not present anymore.
        PricingRule::where('category_id', $categoryId)
            ->whereNotNull('attribute_option_id')
            ->when(!empty($allowedOptionIds), function ($query) use ($allowedOptionIds) {
                $query->whereNotIn('attribute_option_id', $allowedOptionIds);
            }, function ($query) {
                $query->whereNotNull('attribute_option_id');
            })
            ->delete();

        foreach ($rulesCollection as $row) {
            PricingRule::updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'attribute_option_id' => $row['attribute_option_id'],
                ],
                [
                    'pricing_type' => $row['pricing_type'],
                    // Option-level rows store percentage adjustments against base rule.
                    'base_price' => 0,
                    'adjustment_type' => 'percentage',
                    'adjustment_value' => $row['adjustment_percent'],
                    'carbon_per_unit' => $row['carbon_per_unit'],
                    'min_quantity' => 1,
                    'status' => true,
                ]
            );
        }
    }
}
