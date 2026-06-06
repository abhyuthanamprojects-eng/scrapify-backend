<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PickupRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('channel_partner')
            ->withCount('pickupRequests')
            ->with('city.state');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        } elseif ($request->state_id) {
            $query->whereHas('city', function($q) use ($request) {
                $q->where('state_id', $request->state_id);
            });
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $partners = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Partners/Index', [
            'partners' => $partners,
            'filters' => $request->only(['search', 'state_id', 'city_id', 'status']),
            'states' => \App\Models\State::with('cities')->where('status', true)->get(),
        ]);
    }

    public function show(User $partner)
    {
        $partner->load(['city', 'pickupRequests.items.category', 'settlements']);

        $stats = [
            'total_pickups' => $partner->pickupRequests()->count(),
            'completed_pickups' => $partner->pickupRequests()->where('status', 'completed')->count(),
            'pending_settlements' => $partner->settlements()->where('status', 'pending')->sum('total_amount'),
            'paid_settlements' => $partner->settlements()->where('status', 'paid')->sum('total_amount'),
        ];

        return Inertia::render('Admin/Partners/Show', [
            'partner' => $partner,
            'stats' => $stats
        ]);
    }
}
