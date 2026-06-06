<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Services\PickupPriceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class PickupPriceController extends Controller
{
    use ApiResponseTrait;

    public function update(Request $request, $id, PickupPriceService $service)
    {
        $pickup = PickupRequest::findOrFail($id);

        $request->validate([
            'final_amount' => 'required|numeric|min:0',
            'reason'       => 'nullable|string|max:500',
        ]);

        $type = $request->user()->hasRole('admin') ? 'admin' : 'staff';
        $result = $service->modify($pickup, (float) $request->final_amount, $request->user(), $type, $request->reason);

        if (!$result['ok']) return $this->errorResponse($result['message'], 422);
        return $this->successResponse($result['message'], $result['pickup']);
    }

    public function logs($id)
    {
        $pickup = PickupRequest::findOrFail($id);
        return $this->successResponse('pickup.price_logs', $pickup->priceLogs()->with('modifier:id,name')->latest()->get());
    }
}
