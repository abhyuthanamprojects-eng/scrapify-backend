<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryTypeController extends Controller
{
    public function index()
    {
        $types = CategoryType::latest()->paginate(10);
        return Inertia::render('Admin/CategoryTypes/Index', [
            'types' => $types
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/CategoryTypes/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.hi' => 'nullable|string|max:255',
            'status' => 'boolean',
            'show_in_corporate_booking' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['slug'] = Str::slug($validated['name']['en']);

        if (CategoryType::where('slug', $validated['slug'])->exists()) {
            return back()->withErrors(['name.en' => 'A category type with this name already exists.']);
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('category_types', 'public');
        }

        CategoryType::create($validated);

        return redirect()->route('admin.category-types.index')->with('success', 'Category Type created successfully.');
    }

    public function edit(CategoryType $categoryType)
    {
        return Inertia::render('Admin/CategoryTypes/Form', [
            'type' => $categoryType
        ]);
    }

    public function update(Request $request, CategoryType $categoryType)
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.hi' => 'nullable|string|max:255',
            'status' => 'boolean',
            'show_in_corporate_booking' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|boolean',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'status' => $validated['status'] ?? true,
            'show_in_corporate_booking' => $validated['show_in_corporate_booking'] ?? false,
            'slug' => Str::slug($validated['name']['en']),
        ];

        if (CategoryType::where('slug', $updateData['slug'])->where('id', '!=', $categoryType->id)->exists()) {
            return back()->withErrors(['name.en' => 'A category type with this name already exists.']);
        }

        if ($request->hasFile('image')) {
            if ($categoryType->image_path) {
                Storage::disk('public')->delete($categoryType->image_path);
            }
            $updateData['image_path'] = $request->file('image')->store('category_types', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($categoryType->image_path) {
                Storage::disk('public')->delete($categoryType->image_path);
            }
            $updateData['image_path'] = null;
        }

        $categoryType->update($updateData);

        return redirect()->route('admin.category-types.index')->with('success', 'Category Type updated successfully.');
    }

    public function destroy(CategoryType $categoryType)
    {
        if ($categoryType->categories()->exists()) {
            return back()->with('error', 'Cannot delete this type because it has associated categories.');
        }

        if ($categoryType->image_path) {
            Storage::disk('public')->delete($categoryType->image_path);
        }

        $categoryType->delete();

        return redirect()->route('admin.category-types.index')->with('success', 'Category Type deleted successfully.');
    }
}
