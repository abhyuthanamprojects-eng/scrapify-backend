<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Assignment;
use App\Models\PickupBoyLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\City;
use App\Models\State;

class PickupBoyController extends Controller
{
    /**
     * Display a listing of the pickup boys.
     */
    public function index(Request $request)
    {
        $query = User::role('pickup_boy')
            ->with(['city.state'])
            ->withCount([
                'assignments as assigned_pickups_count',
                'assignments as completed_pickups_count' => function ($q) {
                    $q->where('status', 'completed');
                },
                'assignments as today_assignments_count' => function ($q) {
                    $q->whereDate('assigned_at', now()->toDateString())
                      ->whereNotIn('status', ['cancelled', 'rejected']);
                }
            ]);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', (bool)$request->status);
        }

        if ($request->has('is_online') && $request->is_online !== null && $request->is_online !== '') {
            $query->where('is_online', (bool)$request->is_online);
        }
        
        if ($request->has('is_available') && $request->is_available !== null && $request->is_available !== '') {
            $query->where('is_available', (bool)$request->is_available);
        }

        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        } elseif ($request->state_id) {
            $query->whereHas('city', function($q) use ($request) {
                $q->where('state_id', $request->state_id);
            });
        }

        $pickupBoys = $query->latest()->paginate(15)->withQueryString();
        $pickupBoys->getCollection()->transform(function($boy) {
            $boy->is_online = $boy->is_online;
            $boy->is_capacity_full = $boy->is_capacity_full;
            return $boy;
        });

        return Inertia::render('Admin/PickupBoys/Index', [
            'pickupBoys' => $pickupBoys,
            'filters' => $request->only(['search', 'state_id', 'city_id', 'status', 'is_online', 'is_available']),
            'states' => State::with(['cities' => function($q) {
                $q->where('status', true);
            }])->where('status', true)->get(),
        ]);
    }

    /**
     * Display the specified pickup boy.
     */
    public function show($id)
    {
        $pickupBoy = User::role('pickup_boy')
            ->with(['city.state', 'kycDocument'])
            ->withCount([
                'assignments as assigned_pickups_count',
                'assignments as completed_pickups_count' => function ($q) {
                    $q->where('status', 'completed');
                },
                'assignments as today_assignments_count' => function ($q) {
                    $q->whereDate('assigned_at', now()->toDateString())
                      ->whereNotIn('status', ['cancelled', 'rejected']);
                }
            ])
            ->findOrFail($id);

        $assignments = Assignment::where('pickup_boy_id', $id)
            ->with(['pickupRequest.customer', 'pickupRequest.items.category'])
            ->latest('assigned_at')
            ->paginate(15, ['*'], 'assignments_page');

        $trackingHistory = PickupBoyLocation::where('pickup_boy_id', $id)
            ->latest()
            ->limit(50)
            ->get();

        $pickupBoy->is_online = $pickupBoy->is_online;
        $pickupBoy->is_capacity_full = $pickupBoy->is_capacity_full;

        return Inertia::render('Admin/PickupBoys/Show', [
            'pickupBoy' => $pickupBoy,
            'assignments' => $assignments,
            'trackingHistory' => $trackingHistory
        ]);
    }

    /**
     * Update status (Active/Inactive, Available/Unavailable)
     */
    public function update(Request $request, $id)
    {
        $pickupBoy = User::role('pickup_boy')->findOrFail($id);

        $request->validate([
            'status' => 'nullable|boolean',
            'is_available' => 'nullable|boolean',
            'is_online' => 'nullable|boolean',
            'daily_capacity' => 'nullable|integer|min:0',
        ]);

        $updates = [];
        if ($request->has('status')) $updates['status'] = $request->status;
        if ($request->has('is_available')) $updates['is_available'] = $request->is_available;
        if ($request->has('is_online')) $updates['is_online'] = $request->is_online;
        if ($request->has('daily_capacity')) $updates['daily_capacity'] = $request->daily_capacity;

        if (!empty($updates)) {
            $pickupBoy->update($updates);
        }

        return redirect()->back()->with('success', 'Pickup Boy status updated successfully.');
    }
}
