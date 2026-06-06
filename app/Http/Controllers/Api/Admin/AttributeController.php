<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\Category;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all attributes.
     */
    public function index()
    {
        $attributes = Attribute::with('options')->get();
        return $this->successResponse('attributes.fetched', $attributes);
    }

    /**
     * Create a new attribute.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|array', // Enforce localized name
            'name.en' => 'required|string',
            'code' => 'required|string|unique:attributes,code',
            'type' => ['required', Rule::in(['text', 'number', 'select', 'radio', 'checkbox', 'date'])],
            'is_required' => 'boolean',
            'options' => 'required_if:type,select,radio,checkbox|array',
            'options.*' => 'string'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $name = $request->name;
        // Generate slug from English name
        $slugName = $name['en'] ?? reset($name);

        $attribute = Attribute::create([
            'name' => $name,
            'code' => $request->code,
            'slug' => \Illuminate\Support\Str::slug($slugName),
            'type' => $request->type,
            'status' => true
        ]);

        // Handle Options for Select/Radio/Checkbox
        if (in_array($request->type, ['select', 'radio', 'checkbox']) && $request->has('options')) {
            foreach ($request->options as $index => $optionValue) {
                $attribute->options()->create([
                    'value' => ['en' => $optionValue], // Localized Value
                    'sort_order' => $index
                ]);
            }
        }

        return $this->successResponse('attribute.created', $attribute->load('options'), 201);
    }

    /**
     * Update an attribute.
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return $this->errorResponse('attribute.not_found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['text', 'number', 'select', 'radio', 'checkbox', 'date'])],
            'is_required' => 'boolean',
            'status' => 'boolean',
            'options' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $attribute->update($request->only(['name', 'type', 'status']));

        // Update Options (Intelligent sync instead of delete-all to preserve IDs and avoid cascading deletes)
        if ($request->has('options') && in_array($attribute->type, ['select', 'radio', 'checkbox'])) {
            $existingOptions = $attribute->options;
            $newOptionValues = $request->options;
            $optionsToKeepIds = [];

            foreach ($newOptionValues as $index => $optionValue) {
                // Find if an existing option matches this value
                $matchedOption = $existingOptions->first(function($opt) use ($optionValue) {
                    $val = $opt->value;
                    if (is_array($val)) {
                        return ($val['en'] ?? '') === $optionValue;
                    }
                    return $val === $optionValue;
                });

                if ($matchedOption) {
                    // Update/keep existing option to preserve foreign key relationships
                    $matchedOption->update([
                        'value' => ['en' => $optionValue],
                        'sort_order' => $index
                    ]);
                    $optionsToKeepIds[] = $matchedOption->id;
                } else {
                    // Create new option
                    $newOption = $attribute->options()->create([
                        'value' => ['en' => $optionValue],
                        'sort_order' => $index
                    ]);
                    $optionsToKeepIds[] = $newOption->id;
                }
            }

            // Delete options that are not in the new list
            $attribute->options()->whereNotIn('id', $optionsToKeepIds)->delete();
        }

        return $this->successResponse('attribute.updated', $attribute->load('options'));
    }

    /**
     * Delete an attribute.
     */
    public function destroy($id)
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return $this->errorResponse('attribute.not_found', 404);
        }

        $attribute->delete();

        return $this->successResponse('attribute.deleted');
    }

    /**
     * Assign attribute to a category.
     */
    public function assignToCategory(Request $request, $id)
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return $this->errorResponse('attribute.not_found', 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'is_required' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $category = Category::find($request->category_id);

        // Check if already assigned
        if ($category->attributes()->where('attribute_id', $id)->exists()) {
            return $this->errorResponse('attribute.already_assigned', 400);
        }

        $category->attributes()->attach($id, ['is_required' => $request->is_required ?? false]);

        return $this->successResponse('attribute.assigned', null, 200);
    }
}
