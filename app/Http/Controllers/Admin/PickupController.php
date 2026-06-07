<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Assignment;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

class PickupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = PickupRequest::with(['customer', 'warehouse', 'assignment.pickupBoy']);

        // Role-based Scoping (Union Logic)
        if (!$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $hasScope = false;
                
                if ($user->hasRole('warehouse')) {
                    $warehouseIds = \App\Models\Warehouse::where('manager_id', $user->id)->pluck('id');
                    $q->orWhereIn('warehouse_id', $warehouseIds);
                    $hasScope = true;
                }
                
                if ($user->hasRole('channel_partner')) {
                    $q->orWhere('channel_partner_id', $user->channel_partner_id);
                    $hasScope = true;
                }
                
                if ($user->hasRole('pickup_boy')) {
                    $q->orWhereHas('assignment', function ($sub) use ($user) {
                        $sub->where('pickup_boy_id', $user->id);
                    });
                    $hasScope = true;
                }
                
                if (!$hasScope) {
                    $q->whereRaw('1=0');
                }
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->request_type) {
            $query->where('request_type', $request->request_type);
        }

        if ($request->from_date) {
            $query->whereDate('scheduled_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('scheduled_at', '<=', $request->to_date);
        }

        if ($request->pickup_boy_id) {
            $query->whereHas('assignment', function ($q) use ($request) {
                $q->where('pickup_boy_id', $request->pickup_boy_id);
            });
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                    ->orWhere('pickup_code', 'like', '%' . $request->search . '%')
                    ->orWhere('customer_name', 'like', '%' . $request->search . '%')
                    ->orWhere('customer_phone', 'like', '%' . $request->search . '%')
                    ->orWhereHas('partnerCustomer', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('mobile', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('customer', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('phone', 'like', '%' . $request->search . '%');
                    });
            });
        }

        return Inertia::render('Admin/Pickups/Index', [
            'pickups' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'request_type', 'from_date', 'to_date', 'pickup_boy_id']),
            'pickupBoys' => $user->hasRole('admin|channel_partner') ? User::role('pickup_boy')->when($user->hasRole('channel_partner'), fn($q) => $q->where('channel_partner_id', $user->channel_partner_id))->get(['id', 'name']) : [],
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();
        $pickup = PickupRequest::with(['customer', 'partnerCustomer', 'items.category', 'images.pickupItem', 'assignment.pickupBoy', 'warehouse', 'statusLogs'])->findOrFail($id);

        // Role-based Authorization (Union Logic)
        if (!$user->hasRole('admin')) {
            $isAuthorized = false;
            
            if ($user->hasRole('warehouse')) {
                $warehouseIds = \App\Models\Warehouse::where('manager_id', $user->id)->pluck('id')->toArray();
                if (in_array($pickup->warehouse_id, $warehouseIds)) {
                    $isAuthorized = true;
                }
            }
            
            if (!$isAuthorized && $user->hasRole('channel_partner')) {
                if ($pickup->channel_partner_id === $user->channel_partner_id) {
                    $isAuthorized = true;
                }
            }
            
            if (!$isAuthorized && $user->hasRole('pickup_boy')) {
                if ($pickup->assignment?->pickup_boy_id === $user->id) {
                    $isAuthorized = true;
                }
            }
            
            if (!$isAuthorized) {
                abort(403, 'Unauthorized access to this pickup request.');
            }
        }

        return Inertia::render('Admin/Pickups/Show', [
            'pickup' => $pickup,
            'pickupBoys' => User::role('pickup_boy')
                ->when($user->hasRole('channel_partner'), fn($q) => $q->where('channel_partner_id', $user->channel_partner_id))
                ->withCount(['assignments as today_assignments_count' => function($q) {
                    $q->whereDate('assigned_at', now()->toDateString())
                      ->whereNotIn('status', ['cancelled', 'rejected']);
                }])
                ->get()
                ->map(function($pb) {
                    $pb->is_online = $pb->is_online;
                    $pb->is_capacity_full = $pb->is_capacity_full;
                    return $pb;
                }),
        ]);
    }

    public function assign(Request $request, $id, \App\Services\PickupAssignmentService $service)
    {
        $request->validate([
            'pickup_boy_id' => 'required|exists:users,id',
            'notes' => 'nullable|string',
            'override_capacity' => 'nullable|boolean',
        ]);

        $pickupRequest = PickupRequest::findOrFail($id);
        $pickupBoy = User::findOrFail($request->pickup_boy_id);
        $actor = auth()->user();

        if ($pickupRequest->request_type === 'corporate' && $pickupRequest->estimated_amount === null) {
            return back()->with('error', 'Please submit the corporate quote before assigning a pickup boy.');
        }

        if ($actor->hasRole('channel_partner')) {
            abort_unless($pickupRequest->channel_partner_id === $actor->channel_partner_id, 403);
            abort_unless($pickupBoy->channel_partner_id === $actor->channel_partner_id, 403);
        }

        $result = $service->assign(
            $pickupRequest, 
            $pickupBoy, 
            $actor, 
            $actor->hasRole('channel_partner') ? 'channel_partner' : 'admin', 
            $actor->hasRole('channel_partner') ? (bool) $request->override_capacity : true
        );

        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        ActivityLogger::log(
            'assign_pickup',
            'admin',
            'Pickup #' . $pickupRequest->id . ' assigned to ' . $pickupBoy->name . ($request->override_capacity ? ' (Capacity Overridden)' : ''),
            ['pickup_id' => $pickupRequest->id, 'pickup_boy_id' => $request->pickup_boy_id]
        );

        return redirect()->route('admin.pickups.show', $id)->with('success', 'Pickup boy assigned successfully.');
    }

    public function autoAssign(Request $request, \App\Services\PickupAssignmentService $service)
    {
        $pendingPickups = PickupRequest::where('status', 'pending')
            ->whereNotNull('warehouse_id')
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $assignedCount = 0;
        $errors = [];

        foreach ($pendingPickups as $pickup) {
            // Find available drivers in THIS warehouse who are online and NOT at capacity
            $drivers = User::role('pickup_boy')
                ->where(function ($q) use ($pickup) {
                    $q->where('warehouse_id', $pickup->warehouse_id)
                      ->orWhereHas('warehouses', function ($sq) use ($pickup) {
                          $sq->where('warehouses.id', $pickup->warehouse_id)
                             ->where('pickup_boy_warehouse.status', 'active');
                      });
                })
                ->where('users.status', true)
                ->where('is_available', true)
                ->get()
                ->filter(function($driver) use ($pickup) {
                    $isFuturePickup = $pickup->scheduled_at
                        && $pickup->scheduled_at->copy()->timezone('Asia/Kolkata')->toDateString() > now('Asia/Kolkata')->toDateString();

                    return $isFuturePickup || ($driver->is_online && !$driver->is_capacity_full);
                })
                ->sortBy('today_assignments_count'); // Prioritize drivers with less work

            $bestDriver = $drivers->first();

            if ($bestDriver) {
                $result = $service->assign($pickup, $bestDriver, auth()->user(), 'admin', true);
                if ($result['ok']) {
                    $assignedCount++;
                } else {
                    $errors[] = "Pickup #{$pickup->id}: " . $result['message'];
                }
            }
        }

        return back()->with('success', "Auto-allocated {$assignedCount} pickups. " . (count($errors) > 0 ? "Errors: " . implode(', ', array_unique($errors)) : ""));
    }

    public function approveReschedule(Request $request, $id)
    {
        $request->validate([
            'new_scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string'
        ]);

        $pickup = PickupRequest::findOrFail($id);
        
        if ($pickup->status !== 'reschedule_requested') {
            return back()->with('error', 'Pickup is not in a reschedule requested state.');
        }

        DB::transaction(function () use ($pickup, $request) {
            $pickup->update([
                'status' => 'rescheduled',
                'scheduled_at' => $request->new_scheduled_at
            ]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'rescheduled',
                'notes' => 'Reschedule Approved. ' . $request->notes,
                'created_by' => auth()->id()
            ]);

            // Reassign or keep assignment as pending? We will revert assignment to accepted or assigned
            if ($pickup->assignment) {
                // If it was already assigned, we reset the assignment status so the pickup boy knows it's active again
                $pickup->assignment->update(['status' => 'assigned']);
            }
        });

        return redirect()->route('admin.pickups.show', $id)->with('success', 'Reschedule request approved.');
    }

    public function rejectReschedule(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string'
        ]);

        $pickup = PickupRequest::findOrFail($id);
        
        if ($pickup->status !== 'reschedule_requested') {
            return back()->with('error', 'Pickup is not in a reschedule requested state.');
        }

        DB::transaction(function () use ($pickup, $request) {
            // Revert back to assigned or accepted
            $pickup->update([
                'status' => 'pending' // Usually we kick it back to pending or unassigned, let's use pending for safety.
            ]);

            if ($pickup->assignment) {
                $pickup->assignment->update(['status' => 'cancelled']);
            }

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'pending',
                'notes' => 'Reschedule Rejected. ' . $request->notes,
                'created_by' => auth()->id()
            ]);
        });

        return redirect()->route('admin.pickups.show', $id)->with('success', 'Reschedule request rejected and pickup moved to pending.');
    }

    public function submitQuote(Request $request, $id)
    {
        $request->validate([
            'estimated_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $pickup = PickupRequest::findOrFail($id);
        
        if ($pickup->request_type !== 'corporate') {
            return back()->with('error', 'Only corporate bookings can be quoted.');
        }

        DB::transaction(function () use ($pickup, $request) {
            // Ensure metadata is array (may be JSON string from DB)
            $metadata = $pickup->metadata ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?? [];
            }

            $metadata['admin_quote_notes'] = $request->notes;
            $metadata['quoted_at'] = now()->toDateTimeString();
            $metadata['quoted_by'] = auth()->id();

            $pickup->update([
                'estimated_amount' => $request->estimated_amount,
                'metadata' => $metadata,
            ]);

            \App\Models\PickupStatusLog::create([
                'pickup_request_id' => $pickup->id,
                'status' => 'quoted',
                'notes' => 'Corporate Quote Provided: ₹' . $request->estimated_amount . '. ' . $request->notes,
                'created_by' => auth()->id()
            ]);
        });

        return back()->with('success', 'Quote submitted successfully.');
    }
}
