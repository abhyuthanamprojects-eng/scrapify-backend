<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseAppController extends Controller
{
    use ApiResponseTrait;

    /**
     * Resolve which warehouses the current user can act on.
     */
    protected function accessibleWarehouseIds(): array
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return Warehouse::pluck('id')->all();
        }
        if ($user->hasRole('warehouse')) {
            $ids = Warehouse::where('manager_id', $user->id)->pluck('id');

            if ($user->warehouse_id) {
                $ids->push((int) $user->warehouse_id);
            }

            return $ids->unique()->values()->all();
        }
        if ($user->hasRole('channel_partner')) {
            return Warehouse::where('channel_partner_id', $user->channel_partner_id)->pluck('id')->all();
        }
        return [];
    }

    public function profile()
    {
        $user = Auth::user();
        $ids = $this->accessibleWarehouseIds();

        $warehouses = Warehouse::with(['city.state'])
            ->whereIn('id', $ids)
            ->get()
            ->map(function ($w) {
                return [
                    'id'        => $w->id,
                    'name'      => $w->name,
                    'code'      => $w->code,
                    'city'      => $w->city?->name,
                    'state'     => $w->city?->state?->name,
                    'zone'      => $w->zone,
                    'area'      => $w->area,
                    'address'   => $w->address,
                    'latitude'  => $w->latitude,
                    'longitude' => $w->longitude,
                    'service_pincodes' => $w->service_pincodes ?? [],
                    'accepts_corporate' => (bool) $w->accepts_corporate,
                    'accepts_donation' => (bool) $w->accepts_donation,
                    'pickup_boys' => $w->pickupBoys()->wherePivot('status', 'active')->get(['users.id', 'users.name', 'users.phone', 'users.is_available'])->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'mobile' => $u->phone,
                        'status' => $u->is_available ? 'available' : 'busy',
                    ]),
                ];
            });

        return $this->successResponse('warehouse.profile', [
            'user' => $user->only(['id', 'name', 'phone', 'email']),
            'warehouses' => $warehouses,
        ]);
    }

    public function orders(Request $request)
    {
        $ids = $this->accessibleWarehouseIds();

        $query = PickupRequest::with(['customer:id,name,phone', 'items', 'assignment.pickupBoy:id,name,phone'])
            ->whereIn('warehouse_id', $ids);

        if ($request->status)        $query->where('status', $request->status);
        if ($request->request_type)  $query->where('request_type', $request->request_type);
        if ($request->warehouse_id)  $query->where('warehouse_id', $request->warehouse_id);

        return $this->successResponse('warehouse.orders', $query->latest()->paginate($request->input('per_page', 20)));
    }

    public function availablePickupBoys(Request $request)
    {
        $request->validate(['warehouse_id' => 'required|exists:warehouses,id']);
        $ids = $this->accessibleWarehouseIds();

        if (!in_array((int) $request->warehouse_id, $ids)) {
            return $this->errorResponse('warehouse.unauthorized', 403);
        }

        $warehouse = Warehouse::findOrFail($request->warehouse_id);
        $boys = $warehouse->activePickupBoys()
            ->where('users.is_available', true)
            ->where('users.status', true)
            ->get(['users.id', 'users.name', 'users.phone', 'users.is_online', 'users.is_available']);

        return $this->successResponse('pickup_boy.list', $boys);
    }
}
