<?php

namespace App\Http\Controllers\Api\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PickupAssignmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PickupAssignmentService $service) {}

    public function assign(Request $request)
    {
        $request->validate([
            'pickup_request_id' => 'required|exists:pickup_requests,id',
            'pickup_boy_id'     => 'required|exists:users,id',
            'remarks'           => 'nullable|string',
        ]);

        $pickup = PickupRequest::findOrFail($request->pickup_request_id);
        $boy    = User::findOrFail($request->pickup_boy_id);
        $user   = Auth::user();

        // Authorization: user must have access to pickup's warehouse
        if (!$this->canManage($pickup->warehouse_id, $user)) {
            return $this->errorResponse('warehouse.unauthorized', 403);
        }

        $type = $user->hasRole('admin') ? 'admin'
              : ($user->hasRole('warehouse') ? 'warehouse'
              : ($user->hasRole('channel_partner') ? 'channel_partner' : 'unknown'));

        $bypassRealtimeChecks = $user->hasAnyRole(['admin', 'warehouse']);

        $result = $this->service->assign($pickup, $boy, $user, $type, $bypassRealtimeChecks);
        if (!$result['ok']) {
            return $this->errorResponse($result['message'], 422);
        }
        if ($request->filled('remarks')) {
            $result['assignment']->update(['remarks' => $request->remarks]);
        }

        return $this->successResponse($result['message'], $result['assignment']->fresh()->load('pickupBoy:id,name,phone'));
    }

    public function reassign(Request $request, $pickupId)
    {
        $request->merge(['pickup_request_id' => $pickupId]);
        return $this->assign($request);
    }

    protected function canManage(?int $warehouseId, User $user): bool
    {
        if ($user->hasRole('admin')) return true;
        if (!$warehouseId) return false;
        $w = Warehouse::find($warehouseId);
        if (!$w) return false;
        if ($user->hasRole('warehouse'))        return $w->manager_id === $user->id || (int) $user->warehouse_id === (int) $warehouseId;
        if ($user->hasRole('channel_partner'))  return $w->channel_partner_id === $user->channel_partner_id;
        return false;
    }
}
