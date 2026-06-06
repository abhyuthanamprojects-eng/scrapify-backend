<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\City;
use App\Models\InventoryLog;
use App\Services\LocationService;
use App\Services\WarehouseCodeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Warehouse::with('city.state');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
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

        $warehouses = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Warehouses/Index', [
            'warehouses' => $warehouses,
            'filters' => $request->only(['search', 'state_id', 'city_id', 'status']),
            'states' => \App\Models\State::with('cities')->where('status', true)->get(),
        ]);
    }

    public function create()
    {
        $cities = City::with('state')->get();
        $maxServicePincodes = max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10));

        return Inertia::render('Admin/Warehouses/Form', [
            'warehouse' => null,
            'cities' => $cities,
            'maxServicePincodes' => $maxServicePincodes,
        ]);
    }

    public function store(Request $request, LocationService $locator, WarehouseCodeService $codeService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'area' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'service_pincodes' => 'nullable|array|max:' . max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10)),
            'service_pincodes.*' => 'nullable|string|regex:/^\d{6}$/',
            'service_types' => 'nullable|array',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'accepts_corporate' => 'boolean',
            'accepts_donation' => 'boolean',
        ]);

        // Auto-resolve city/zone if not supplied
        if (empty($validated['city_id']) || empty($validated['zone'])) {
            $geo = $locator->reverseGeocode((float) $validated['latitude'], (float) $validated['longitude']);
            if (empty($validated['city_id']) && $geo['city']) $validated['city_id'] = $geo['city']->id;
            if (empty($validated['zone'])) $validated['zone'] = $geo['zone'];
        }

        if (empty($validated['city_id'])) {
            return back()->withErrors(['city_id' => 'Could not auto-detect city. Please pick manually.'])->withInput();
        }

        $city = City::with('state')->findOrFail($validated['city_id']);
        $validated['code'] = $codeService->generate($city);
        $validated['service_pincodes'] = Warehouse::normalizePincodeList(
            $request->input('service_pincodes', []),
            max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10))
        );

        $duplicatePincodes = Warehouse::duplicateServicePincodes($validated['service_pincodes']);
        if (!empty($duplicatePincodes)) {
            return back()->withErrors([
                'service_pincodes' => $this->duplicatePincodeMessage($duplicatePincodes),
            ])->withInput();
        }

        if ($request->user()->hasRole('channel_partner')) {
            $validated['channel_partner_id'] = $request->user()->channel_partner_id;
        }

        Warehouse::create($validated);

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    public function show(Warehouse $warehouse)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && $user->hasRole('warehouse') && $warehouse->manager_id !== $user->id) {
            abort(403, 'Unauthorized access to this warehouse.');
        }

        $warehouse->load(['city.state', 'inventoryLogs.category', 'inventoryLogs.pickupRequest', 'pickupBoys']);

        $availablePickupBoys = User::role('pickup_boy')->select('id', 'name', 'phone')->get();

        return Inertia::render('Admin/Warehouses/Show', [
            'warehouse' => $warehouse,
            'availablePickupBoys' => $availablePickupBoys,
        ]);
    }

    public function edit(Warehouse $warehouse)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && $user->hasRole('warehouse') && $warehouse->manager_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $cities = City::with('state')->get();
        $maxServicePincodes = max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10));

        return Inertia::render('Admin/Warehouses/Form', [
            'warehouse' => $warehouse,
            'cities' => $cities,
            'maxServicePincodes' => $maxServicePincodes,
        ]);
    }

    public function update(Request $request, Warehouse $warehouse, LocationService $locator)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'area' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'service_pincodes' => 'nullable|array|max:' . max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10)),
            'service_pincodes.*' => 'nullable|string|regex:/^\d{6}$/',
            'service_types' => 'nullable|array',
            'capacity' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'accepts_corporate' => 'boolean',
            'accepts_donation' => 'boolean',
        ]);

        // If lat/lng changed and zone empty, refresh zone
        if (empty($validated['zone']) && !empty($validated['latitude']) && !empty($validated['longitude'])) {
            $geo = $locator->reverseGeocode((float) $validated['latitude'], (float) $validated['longitude']);
            if ($geo['zone']) $validated['zone'] = $geo['zone'];
        }

        $validated['service_pincodes'] = Warehouse::normalizePincodeList(
            $request->input('service_pincodes', []),
            max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10))
        );

        $duplicatePincodes = Warehouse::duplicateServicePincodes($validated['service_pincodes'], $warehouse->id);
        if (!empty($duplicatePincodes)) {
            return back()->withErrors([
                'service_pincodes' => $this->duplicatePincodeMessage($duplicatePincodes),
            ])->withInput();
        }

        // Code is immutable post-create
        $warehouse->update($validated);

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return redirect()->route('admin.warehouses.index')
            ->with('success', 'Warehouse deleted successfully.');
    }

    protected function duplicatePincodeMessage(array $duplicates): string
    {
        $items = collect($duplicates)
            ->map(fn ($warehouse, $pincode) => "{$pincode} is already assigned to {$warehouse['warehouse_name']}")
            ->values()
            ->all();

        return 'Each service pincode can be assigned to only one warehouse. ' . implode('; ', $items) . '.';
    }

    public function reverseGeocode(Request $request, LocationService $locator)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $geo = $locator->reverseGeocode((float) $request->latitude, (float) $request->longitude);

        return response()->json([
            'status' => true,
            'data'   => [
                'formatted_address' => $geo['formatted_address'],
                'city_id'   => $geo['city']?->id,
                'city_name' => $geo['city']?->name ?? $geo['raw_city'],
                'state_id'  => $geo['state']?->id,
                'state_name'=> $geo['state']?->name ?? $geo['raw_state'],
                'zone'      => $geo['zone'],
                'pincode'   => $geo['pincode'],
            ],
        ]);
    }

    public function pickupBoys(Warehouse $warehouse)
    {
        $boys = $warehouse->pickupBoys()->withPivot(['status', 'created_by'])->get();
        return response()->json(['status' => true, 'data' => $boys]);
    }

    public function attachPickupBoy(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'pickup_boy_id' => 'required|exists:users,id',
            'status'        => 'sometimes|in:active,inactive',
        ]);

        $user = User::findOrFail($request->pickup_boy_id);
        if (!$user->hasRole('pickup_boy')) {
            return response()->json(['status' => false, 'message' => 'pickup_boy.invalid'], 422);
        }

        $warehouse->pickupBoys()->syncWithoutDetaching([
            $user->id => [
                'status'     => $request->input('status', 'active'),
                'created_by' => $request->user()->id,
            ],
        ]);

        return response()->json(['status' => true, 'message' => 'pickup_boy.attached']);
    }

    public function detachPickupBoy(Warehouse $warehouse, $userId)
    {
        $warehouse->pickupBoys()->detach($userId);
        return response()->json(['status' => true, 'message' => 'pickup_boy.detached']);
    }
}
