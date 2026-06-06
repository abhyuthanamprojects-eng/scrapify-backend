<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\PickupItem;
use App\Models\PickupRequest;
use App\Services\ActivityLogger;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PickupRequestController extends Controller
{
    use ApiResponseTrait;

    /**
     * List customer's pickup requests.
     */
    public function index(Request $request)
    {
        $requests = PickupRequest::where('customer_id', $request->user()->id)
            ->with(['items.category', 'images'])
            ->latest()
            ->paginate(10);

        return $this->successResponse('general.success', $requests);
    }

    /**
     * Create a new pickup request.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'required|date|after:now',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:categories,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.image' => 'nullable|image|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $pickupRequest = PickupRequest::create([
                'customer_id' => $request->user()->id,
                'scheduled_at' => $request->scheduled_at,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'pending',
                'estimated_amount' => 0, // Calculated later or client side
            ]);

            foreach ($request->items as $itemData) {
                // Handle Item Image Upload if present
                $imagePath = null;
                if (isset($itemData['image']) && $itemData['image'] instanceof \Illuminate\Http\UploadedFile) {
                    $imagePath = $itemData['image']->store('pickup_items', 'public');
                }

                PickupItem::create([
                    'pickup_request_id' => $pickupRequest->id,
                    'category_id' => $itemData['category_id'],
                    'quantity' => $itemData['quantity'],
                    // Price per unit logic? For now 0, detailed pricing is Admin/Pickup Boy job
                    'price_per_unit' => 0,
                    'total_price' => 0,
                    'image_path' => $imagePath,
                ]);
            }

            // Log Activity
            ActivityLogger::log('create', 'pickup', 'Created pickup request #' . $pickupRequest->id, ['id' => $pickupRequest->id]);

            DB::commit();

            return $this->successResponse('pickup.created', $pickupRequest->load('items'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Show pickup request details.
     */
    public function show($id)
    {
        $request = PickupRequest::where('customer_id', request()->user()->id)
            ->with(['items.category', 'images', 'assignment.pickupBoy'])
            ->find($id);

        if (!$request) {
            return $this->errorResponse('pickup.not_found', 404);
        }

        return $this->successResponse('general.success', $request);
    }
}
