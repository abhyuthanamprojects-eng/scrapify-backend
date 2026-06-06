<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $query = Attribute::with('categories');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                // Search in JSON name field and code
                $q->where('name->en', 'like', '%' . $request->search . '%')
                  ->orWhere('name->hi', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $attributes = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Attributes/Index', [
            'attributes' => $attributes,
            'filters' => $request->only(['search', 'type']),
            'types' => ['text', 'number', 'select', 'radio', 'checkbox', 'date'],
        ]);
    }

    public function create()
    {
        $categories = Category::all();

        return Inertia::render('Admin/Attributes/Form', [
            'attribute' => null,
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.hi' => 'nullable|string|max:255',
            'code' => 'required|string|unique:attributes,code',
            'type' => 'required|in:text,number,select,radio,checkbox,date',
            'is_required' => 'boolean',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $attribute = Attribute::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type'],
            'is_required' => $validated['is_required'] ?? false,
        ]);

        // Create options if provided
        if (!empty($validated['options'])) {
            foreach ($validated['options'] as $optionValue) {
                AttributeOption::create([
                    'attribute_id' => $attribute->id,
                    'value' => ['en' => $optionValue, 'hi' => $optionValue],
                ]);
            }
        }

        // Attach to categories if provided
        if (!empty($validated['categories'])) {
            $attribute->categories()->attach($validated['categories']);
        }

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute created successfully.');
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load(['options', 'categories']);
        $categories = Category::all();

        return Inertia::render('Admin/Attributes/Form', [
            'attribute' => $attribute,
            'categories' => $categories
        ]);
    }

    public function update(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.hi' => 'nullable|string|max:255',
            'code' => 'required|string|unique:attributes,code,' . $attribute->id,
            'type' => 'required|in:text,number,select,radio,checkbox,date',
            'is_required' => 'boolean',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $attribute->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type'],
            'is_required' => $validated['is_required'] ?? false,
        ]);

        // Update options (intelligent sync instead of deleting all to preserve foreign key relationships)
        if (!empty($validated['options'])) {
            $existingOptions = $attribute->options;
            $newOptionValues = $validated['options'];
            $optionsToKeepIds = [];

            foreach ($newOptionValues as $optionValue) {
                // Find if an existing option matches this value
                $matchedOption = $existingOptions->first(function($opt) use ($optionValue) {
                    $val = $opt->value;
                    if (is_array($val)) {
                        return ($val['en'] ?? '') === $optionValue || ($val['hi'] ?? '') === $optionValue;
                    }
                    return $val === $optionValue;
                });

                if ($matchedOption) {
                    // Update/keep existing option to preserve foreign key relationships
                    $matchedOption->update([
                        'value' => ['en' => $optionValue, 'hi' => $matchedOption->value['hi'] ?? $optionValue]
                    ]);
                    $optionsToKeepIds[] = $matchedOption->id;
                } else {
                    // Create new option
                    $newOption = $attribute->options()->create([
                        'value' => ['en' => $optionValue, 'hi' => $optionValue],
                    ]);
                    $optionsToKeepIds[] = $newOption->id;
                }
            }

            // Delete options that are not in the new list
            $attribute->options()->whereNotIn('id', $optionsToKeepIds)->delete();
        } else {
            // Delete all options
            $attribute->options()->delete();
        }

        // Sync categories
        if (isset($validated['categories'])) {
            $attribute->categories()->sync($validated['categories']);
        }

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully.');
    }
}
