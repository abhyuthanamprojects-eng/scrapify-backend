<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\InventoryLog;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Assignment;
use App\Models\PickupAssignmentHistory;
use App\Models\PickupStatusLog;
use App\Services\PickupAssignmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;



class WarehouseController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected PickupAssignmentService $assignmentService
    ) {}

    /**
     * List all warehouses.
     */
    public function index(Request $request)
    {
        $query = Warehouse::with('city.state');

        // Filter by city
        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $warehouses = $query->get();

        return $this->successResponse('warehouses.fetched', $warehouses);
    }

    /**
     * Get warehouse details with inventory.
     */
    public function show($id)
    {
        $warehouse = Warehouse::with([
            'city.state',
            'inventoryLogs' => function ($query) {
                $query->latest()->limit(50);
            }
        ])->find($id);

        if (!$warehouse) {
            return $this->errorResponse('warehouse.not_found', 404);
        }

        return $this->successResponse('warehouse.fetched', $warehouse);
    }

    /**
     * Update warehouse inventory after pickup delivery.
     */
    public function updateInventory(Request $request, $warehouseId)
    {
        $validator = Validator::make($request->all(), [
            'pickup_request_id' => 'required|exists:pickup_requests,id',
            'category_id' => 'required|exists:categories,id',
            'weight' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'condition' => 'required|in:excellent,good,fair,poor,scrap',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $warehouse = Warehouse::find($warehouseId);

        if (!$warehouse) {
            return $this->errorResponse('warehouse.not_found', 404);
        }

        try {
            DB::beginTransaction();

            // Create inventory log
            $inventoryLog = InventoryLog::create([
                'warehouse_id' => $warehouseId,
                'pickup_request_id' => $request->pickup_request_id,
                'category_id' => $request->category_id,
                'weight' => $request->weight,
                'quantity' => $request->quantity,
                'condition' => $request->condition,
                'notes' => $request->notes,
                'action' => 'received'
            ]);

            // Update pickup request warehouse assignment
            PickupRequest::where('id', $request->pickup_request_id)
                ->update(['warehouse_id' => $warehouseId]);

            DB::commit();

            return $this->successResponse('inventory.updated', $inventoryLog, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Get warehouse inventory summary.
     */
    public function inventorySummary($warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);

        if (!$warehouse) {
            return $this->errorResponse('warehouse.not_found', 404);
        }

        $summary = [
            'total_items_received' => InventoryLog::where('warehouse_id', $warehouseId)
                ->where('action', 'received')
                ->sum('quantity'),
            'total_weight' => InventoryLog::where('warehouse_id', $warehouseId)
                ->where('action', 'received')
                ->sum('weight'),
            'by_category' => InventoryLog::where('warehouse_id', $warehouseId)
                ->where('action', 'received')
                ->select('category_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(weight) as total_weight'))
                ->groupBy('category_id')
                ->with('category:id,name')
                ->get(),
            'by_condition' => InventoryLog::where('warehouse_id', $warehouseId)
                ->where('action', 'received')
                ->select('condition', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as total_quantity'))
                ->groupBy('condition')
                ->get()
        ];

        return $this->successResponse('inventory.summary_fetched', $summary);
    }

    /**
     * Get recent inventory logs.
     */
    public function inventoryLogs(Request $request, $warehouseId)
    {
        $logs = InventoryLog::where('warehouse_id', $warehouseId)
            ->with(['pickupRequest', 'category'])
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse('inventory.logs_fetched', $logs);
    }

    protected function getWarehousePickupBoysQuery($warehouseId)
    {
        return \App\Models\User::role('pickup_boy')
            ->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->orWhereHas('warehouses', function ($sq) use ($warehouseId) {
                        $sq->where('warehouses.id', $warehouseId)
                            ->where('pickup_boy_warehouse.status', 'active');
                    });
            });
    }

    protected function getWarehouse($user)
    {
        // 1. Prioritize explicit warehouse_id if set on user record
        if ($user->warehouse_id) {
            $warehouse = Warehouse::with('city')->find($user->warehouse_id);
            if ($warehouse)
                return $warehouse;
        }

        // 2. Fallback to manager_id lookup
        $warehouse = Warehouse::with('city')->where('manager_id', $user->id)->first();
        if ($warehouse)
            return $warehouse;

        // 3. If user is a channel partner, they might have warehouses
        if ($user->channel_partner_id) {
            $warehouse = Warehouse::with('city')->where('channel_partner_id', $user->channel_partner_id)->first();
            if ($warehouse)
                return $warehouse;
        }

        // 4. If still not found and user is admin or warehouse manager, we don't default to first warehouse anymore
        // to avoid incorrect data context. Controller actions should handle specific warehouse lookup.

        return null;

        return null;
    }

    /**
     * Dashboard for warehouse manager/user.
     */
    public function dashboard(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $warehouse = $this->getWarehouse($user);

        if (!$warehouse) {
            return $this->errorResponse('warehouse.no_warehouse_assigned', 404);
        }

        $this->autoRescheduleMissedWarehousePickups($warehouse->id, $user->id);

        $stats = [
            'total_requests' => PickupRequest::where('warehouse_id', $warehouse->id)->count(),
            'today_requests' => PickupRequest::where('warehouse_id', $warehouse->id)->whereDate('created_at', now()->toDateString())->count(),
            'unassigned_requests' => PickupRequest::where('warehouse_id', $warehouse->id)
                ->where(function ($q) {
                    $q->whereIn('status', ['pending'])
                        ->orWhereIn('status_new', ['pending_warehouse', 'warehouse_receive_pending']);
                })->count(),
            'assigned_requests' => PickupRequest::where('warehouse_id', $warehouse->id)->whereIn('status', ['assigned', 'accepted'])->count(),
            'active_pickups' => PickupRequest::where('warehouse_id', $warehouse->id)->whereIn('status', ['on_the_way'])->count(),
            'completed_pickups' => PickupRequest::where('warehouse_id', $warehouse->id)->whereIn('status', ['completed', 'picked_up'])->count(),
            'warehouse_received' => PickupRequest::where('warehouse_id', $warehouse->id)->whereIn('status_new', ['warehouse_received'])->count(),
            'rescheduled_requests' => PickupRequest::where('warehouse_id', $warehouse->id)->whereIn('status', ['rescheduled', 'reschedule_requested'])->count(),
            'total_pickup_boys' => $this->getWarehousePickupBoysQuery($warehouse->id)->count(),
            'active_pickup_boys' => $this->getWarehousePickupBoysQuery($warehouse->id)->where('users.status', true)->count(),
            'available_pickup_boys' => $this->getWarehousePickupBoysQuery($warehouse->id)->where('is_available', true)->count(),
            'online_pickup_boys' => $this->getWarehousePickupBoysQuery($warehouse->id)->online()->count(),
            'capacity_full_drivers' => $this->getWarehousePickupBoysQuery($warehouse->id)->get()->filter->is_capacity_full->count(),
        ];

        $recent_requests = PickupRequest::where('warehouse_id', $warehouse->id)
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereIn('status', ['pending', 'assigned', 'accepted', 'on_the_way', 'completed', 'picked_up'])
                        ->orWhereIn('status_new', ['pending_warehouse', 'warehouse_receive_pending', 'warehouse_received', 'pickup_boy_assigned', 'pickup_started', 'pickup_completed']);
                });
            })
            ->latest()
            ->take(5)
            ->get();

        return $this->successResponse('warehouse.dashboard', [
            'warehouse' => $warehouse,
            'metrics' => $stats,
            'recent_requests' => $recent_requests
        ]);
    }

    /**
     * Get list of outbound shipments from this warehouse.
     */
    public function shipments(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $warehouse = $this->getWarehouse($user);

        if (!$warehouse) {
            return $this->errorResponse('warehouse.no_warehouse_assigned', 404);
        }

        $shipments = InventoryLog::where('warehouse_id', $warehouse->id)
            ->whereIn('action', ['shipped', 'outbound'])
            ->with(['category'])
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse('warehouse.shipments_fetched', $shipments);
    }

    public function profile(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $warehouse = $this->getWarehouse($user);

        return $this->successResponse('warehouse.profile_fetched', [
            'user' => $user,
            'warehouse' => $warehouse,
            'support' => [
                'email' => 'support@scrapify.com',
                'phone' => '+1800000000'
            ]
        ]);
    }

    public function requests(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $warehouse = $this->getWarehouse($user);

        if (!$warehouse)
            return $this->errorResponse('warehouse.no_warehouse_assigned', 404);

        $this->autoRescheduleMissedWarehousePickups($warehouse->id, $user->id);

        $query = PickupRequest::where('warehouse_id', $warehouse->id)
            ->with(['customer', 'assignment.pickupBoy']);

        if ($request->has('status'))
            $query->where('status', $request->status);
        if ($request->has('pickup_boy_id')) {
            $query->whereHas('assignment', function ($q) use ($request) {
                $q->where('pickup_boy_id', $request->pickup_boy_id);
            });
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pickup_code', 'like', "%$search%")
                    ->orWhere('customer_name', 'like', "%$search%")
                    ->orWhere('customer_phone', 'like', "%$search%");
            });
        }

        $requests = $query->latest()->paginate($request->per_page ?? 20);
        return $this->paginatedResponse('warehouse.requests_fetched', $requests);
    }

    protected function autoRescheduleMissedWarehousePickups(int $warehouseId, ?int $actorUserId = null): void
    {
        app(\App\Services\PickupAssignmentService::class)
            ->autoRescheduleOverduePickups($actorUserId, $warehouseId);
    }

    public function showRequest($id)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $warehouse = $this->getWarehouse($user);

        if (!$warehouse)
            return $this->errorResponse('warehouse.no_warehouse_assigned', 404);

        $pickup = PickupRequest::where('warehouse_id', $warehouse->id)
            ->with(['customer', 'items.category', 'images', 'assignment.pickupBoy', 'statusLogs', 'assignmentHistories.oldPickupBoy', 'assignmentHistories.newPickupBoy', 'assignmentHistories.assignedByUser'])
            ->find($id);

        if (!$pickup)
            return $this->errorResponse('warehouse.request_not_found', 404);

        return $this->successResponse('warehouse.request_fetched', $pickup);
    }

    public function pickupBoys(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $warehouse = $this->getWarehouse($user);

        if (!$warehouse)
            return $this->errorResponse('warehouse.no_warehouse_assigned', 404);

        $agents = $this->getWarehousePickupBoysQuery($warehouse->id)
            ->withCount([
                'assignments as current_assignment_count' => function ($q) {
                    $q->whereIn('status', ['assigned', 'accepted']);
                },
                'assignments as completed_count' => function ($q) {
                    $q->where('status', 'completed');
                },
                'assignments as today_assignments_count' => function ($q) {
                    $q->whereDate('assigned_at', now()->toDateString())
                      ->whereNotIn('status', ['cancelled', 'rejected']);
                }
            ])->get()->map(function($agent) {
                $agent->is_online = $agent->is_online; // Force accessor
                $agent->is_capacity_full = $agent->is_capacity_full; // Force accessor
                return $agent;
            });

        return $this->successResponse('warehouse.pickup_boys_fetched', $agents);
    }

    public function showPickupBoy($id)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $warehouse = $this->getWarehouse($user);

        if (!$warehouse)
            return $this->errorResponse('warehouse.no_warehouse_assigned', 404);

        $agent = $this->getWarehousePickupBoysQuery($warehouse->id)
            ->withCount([
                'assignments as current_assignment_count' => function ($q) {
                    $q->whereIn('status', ['assigned', 'accepted']);
                },
                'assignments as completed_count' => function ($q) {
                    $q->where('status', 'completed');
                }
            ])->find($id);

        if (!$agent)
            return $this->errorResponse('warehouse.pickup_boy_not_found', 404);

        return $this->successResponse('warehouse.pickup_boy_fetched', $agent);
    }

    public function assignablePickupBoys($id)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Find the specific request first to know which warehouse we are dealing with
        $pickup = PickupRequest::find($id);
        if (!$pickup)
            return $this->errorResponse('warehouse.request_not_found', 404);

        $warehouseId = $pickup->warehouse_id;

        // If no warehouse is assigned to the request, we can't show boys
        if (!$warehouseId) {
            return $this->errorResponse('warehouse.no_warehouse_assigned_to_request', 400);
        }

        // Permission check: if not admin, ensure user manages THIS warehouse
        if (!$user->hasRole('admin')) {
            $managedWarehouse = $this->getWarehouse($user);
            if (!$managedWarehouse || $managedWarehouse->id != $warehouseId) {
                return $this->errorResponse('warehouse.unauthorized_access', 403);
            }
        }

        $agents = $this->getWarehousePickupBoysQuery($warehouseId)
            ->withCount(['assignments as today_assignments_count' => function($q) {
                $q->whereDate('assigned_at', now()->toDateString())
                  ->whereNotIn('status', ['cancelled', 'rejected']);
            }])
            ->get()
            ->map(function($agent) {
                $agent->is_online = $agent->is_online;
                $agent->is_capacity_full = $agent->is_capacity_full;
                return $agent;
            });

        return $this->successResponse('warehouse.assignable_pickup_boys_fetched', $agents);
    }

    public function assignPickup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pickup_boy_id' => 'required|exists:users,id',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails())
            return $this->validationErrorResponse($validator->errors());

        $user = \Illuminate\Support\Facades\Auth::user();

        $pickupRequest = PickupRequest::find($id);
        if (!$pickupRequest)
            return $this->errorResponse('warehouse.request_not_found', 404);

        $warehouseId = $pickupRequest->warehouse_id;
        if (!$warehouseId)
            return $this->errorResponse('warehouse.no_warehouse_assigned_to_request', 400);

        // Permission check
        if (!$user->hasRole('admin')) {
            $managedWarehouse = $this->getWarehouse($user);
            if (!$managedWarehouse || $managedWarehouse->id != $warehouseId) {
                return $this->errorResponse('warehouse.unauthorized_access', 403);
            }
        }

        $pickupBoy = $this->getWarehousePickupBoysQuery($warehouseId)->find($request->pickup_boy_id);
        if (!$pickupBoy || !$pickupBoy->hasRole('pickup_boy')) {
            return $this->errorResponse('warehouse.invalid_pickup_boy', 400);
        }

        $result = $this->assignmentService->assign(
            $pickupRequest,
            $pickupBoy,
            $user,
            $user->hasRole('admin') ? 'admin' : 'warehouse',
            true
        );

        if (!$result['ok']) {
            return $this->errorResponse($result['message'], 422);
        }

        if ($request->filled('notes')) {
            $result['assignment']->update(['remarks' => $request->notes]);
        }

        PickupAssignmentHistory::create([
            'pickup_request_id' => $pickupRequest->id,
            'old_pickup_boy_id' => null,
            'new_pickup_boy_id' => $pickupBoy->id,
            'assigned_by_user_id' => $user->id,
            'reason' => 'Initial assignment from warehouse',
        ]);

        return $this->successResponse('warehouse.pickup_assigned', ['status' => 'assigned']);
    }

    public function reassignPickup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pickup_boy_id' => 'required|exists:users,id',
            'reason' => 'nullable|string'
        ]);

        if ($validator->fails())
            return $this->validationErrorResponse($validator->errors());

        $user = \Illuminate\Support\Facades\Auth::user();

        $pickupRequest = PickupRequest::find($id);
        if (!$pickupRequest)
            return $this->errorResponse('warehouse.request_not_found', 404);

        $warehouseId = $pickupRequest->warehouse_id;
        if (!$warehouseId)
            return $this->errorResponse('warehouse.no_warehouse_assigned_to_request', 400);

        // Permission check
        if (!$user->hasRole('admin')) {
            $managedWarehouse = $this->getWarehouse($user);
            if (!$managedWarehouse || $managedWarehouse->id != $warehouseId) {
                return $this->errorResponse('warehouse.unauthorized_access', 403);
            }
        }

        $newPickupBoy = $this->getWarehousePickupBoysQuery($warehouseId)->find($request->pickup_boy_id);
        if (!$newPickupBoy || !$newPickupBoy->hasRole('pickup_boy')) {
            return $this->errorResponse('warehouse.invalid_pickup_boy', 400);
        }

        $oldAssignment = Assignment::where('pickup_request_id', $pickupRequest->id)
            ->whereNotIn('status', ['completed', 'pickup_completed', 'cancelled', 'reassigned', 'rejected'])
            ->latest('assigned_at')
            ->first();
        $oldPickupBoyId = $oldAssignment?->pickup_boy_id;

        $result = $this->assignmentService->assign(
            $pickupRequest,
            $newPickupBoy,
            $user,
            $user->hasRole('admin') ? 'admin' : 'warehouse',
            true
        );

        if (!$result['ok']) {
            return $this->errorResponse($result['message'], 422);
        }

        PickupAssignmentHistory::create([
            'pickup_request_id' => $pickupRequest->id,
            'old_pickup_boy_id' => $oldPickupBoyId,
            'new_pickup_boy_id' => $newPickupBoy->id,
            'assigned_by_user_id' => $user->id,
            'reason' => $request->reason,
        ]);

        if ($request->filled('reason')) {
            PickupStatusLog::create([
                'pickup_request_id' => $pickupRequest->id,
                'status' => 'reassigned',
                'notes' => 'Pickup reassigned from warehouse. Reason: ' . $request->reason,
                'created_by' => $user->id
            ]);
        }

        return $this->successResponse('warehouse.pickup_reassigned', ['status' => 'assigned']);
    }
}
