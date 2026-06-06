<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationController extends Controller
{
    public function index()
    {
        $states = State::withCount('cities')->get();
        $cities = City::with('state')->get();

        return Inertia::render('Admin/Locations/Index', [
            'states' => $states,
            'cities' => $cities
        ]);
    }

    public function storeState(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:states,code',
            'status' => 'boolean',
        ]);

        State::create($validated);

        return redirect()->back()
            ->with('success', 'State created successfully.');
    }

    public function storeCity(Request $request)
    {
        $validated = $request->validate([
            'state_id' => 'required|exists:states,id',
            'name' => 'required|string|max:255',
            'default_zone' => 'nullable|string|max:255',
            'status' => 'boolean',
        ]);

        City::create($validated);

        return redirect()->back()
            ->with('success', 'City created successfully.');
    }

    public function updateState(Request $request, State $state)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:states,code,' . $state->id,
            'status' => 'boolean',
        ]);

        $state->update($validated);

        return redirect()->back()
            ->with('success', 'State updated successfully.');
    }

    public function updateCity(Request $request, City $city)
    {
        $validated = $request->validate([
            'state_id' => 'required|exists:states,id',
            'name' => 'required|string|max:255',
            'default_zone' => 'nullable|string|max:255',
            'status' => 'boolean',
        ]);

        $city->update($validated);

        return redirect()->back()
            ->with('success', 'City updated successfully.');
    }
}
