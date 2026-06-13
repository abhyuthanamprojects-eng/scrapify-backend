<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeBannerController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:8192',
            'text' => 'nullable|string|max:120',
        ]);

        $path = $request->file('image')->store('home_banners', 'public');
        $maxOrder = HomeBanner::max('sort_order');

        HomeBanner::create([
            'image_path' => $path,
            'text' => $request->input('text', ''),
            'sort_order' => $maxOrder === null ? 0 : $maxOrder + 1,
        ]);

        return back()->with('success', 'Banner added successfully.');
    }

    public function update(Request $request, HomeBanner $homeBanner)
    {
        $request->validate([
            'image' => 'nullable|image|max:8192',
            'text' => 'nullable|string|max:120',
        ]);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($homeBanner->image_path);
            $homeBanner->image_path = $request->file('image')->store('home_banners', 'public');
        }

        if ($request->has('text')) {
            $homeBanner->text = $request->input('text', '');
        }

        $homeBanner->save();

        return back()->with('success', 'Banner updated successfully.');
    }

    public function destroy(HomeBanner $homeBanner)
    {
        Storage::disk('public')->delete($homeBanner->image_path);
        $homeBanner->delete();

        return back()->with('success', 'Banner removed successfully.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:home_banners,id',
        ]);

        foreach ($request->input('ids') as $index => $id) {
            HomeBanner::where('id', $id)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Banner order updated.');
    }
}
