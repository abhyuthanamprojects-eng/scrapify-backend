<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\City;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'city']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->role) {
            $query->role($request->role);
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        } elseif ($request->state_id) {
            $query->whereHas('city', function($q) use ($request) {
                $q->where('state_id', $request->state_id);
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status', 'state_id', 'city_id']),
            'roles' => Role::all()->pluck('name'),
            'states' => \App\Models\State::with(['cities' => function($q) {
                $q->where('status', true);
            }])->where('status', true)->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Users/Form', [
            'roles' => Role::all()->pluck('name'),
            'states' => \App\Models\State::with(['cities' => function($q) {
                $q->where('status', true);
            }])->where('status', true)->get(),
            'warehouses' => \App\Models\Warehouse::where('status', true)->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
            'city_id' => 'nullable|exists:cities,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'required|boolean',
            'daily_capacity' => 'nullable|integer|min:1',
            'is_manual_offline' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city_id' => $request->city_id,
            'warehouse_id' => $request->warehouse_id,
            'password' => Hash::make($request->password),
            'status' => $request->status,
            'daily_capacity' => $request->daily_capacity ?? 4,
            'is_manual_offline' => $request->is_manual_offline ?? false,
            'channel_partner_id' => $request->user()->hasRole('channel_partner') ? $request->user()->channel_partner_id : null,
        ]);

        $user->syncRoles($request->roles);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => $user->load(['roles', 'addresses.city.state']),
            'roles' => Role::all()->pluck('name'),
            'states' => \App\Models\State::with(['cities' => function($q) {
                $q->where('status', true);
            }])->where('status', true)->get(),
            'warehouses' => \App\Models\Warehouse::where('status', true)->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/|unique:users,phone,' . $user->id,
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
            'city_id' => 'nullable|exists:cities,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'status' => 'required|boolean',
            'daily_capacity' => 'nullable|integer|min:1',
            'is_manual_offline' => 'nullable|boolean',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'city_id' => $request->city_id,
            'warehouse_id' => $request->warehouse_id,
            'status' => $request->status,
            'daily_capacity' => $request->daily_capacity ?? $user->daily_capacity,
            'is_manual_offline' => $request->is_manual_offline ?? $user->is_manual_offline,
        ]);

        if ($request->password) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles($request->roles);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
