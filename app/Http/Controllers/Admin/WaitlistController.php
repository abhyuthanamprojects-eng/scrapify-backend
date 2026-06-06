<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WaitlistController extends Controller
{
    public function index(Request $request)
    {
        $query = Waitlist::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $entries = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Waitlist/Index', [
            'entries' => $entries,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function update(Request $request, Waitlist $waitlist)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,planned,launched,closed',
        ]);

        $waitlist->update($validated);

        return back()->with('success', 'Waitlist status updated successfully.');
    }

    public function destroy(Waitlist $waitlist)
    {
        $waitlist->delete();

        return back()->with('success', 'Waitlist entry deleted successfully.');
    }
}
