<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PickupImage;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    use ApiResponseTrait;

    /**
     * Upload pickup request images.
     */
    public function uploadPickupImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_request_id' => 'nullable|exists:pickup_requests,id',
            'pickup_item_id' => 'nullable|exists:pickup_items,id',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'type' => 'nullable|in:booking,verification,pickup,item,delivery_proof',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $uploadedImages = [];

        try {
            foreach ($request->file('images') as $image) {
                // Store image in public/pickup_images directory
                $path = $image->store('pickup_images', 'public');

                // Create database record
                $pickupImage = PickupImage::create([
                    'pickup_request_id' => $request->pickup_request_id,
                    'pickup_item_id' => $request->pickup_item_id,
                    'image_path' => $path,
                    'type' => $request->type ?? 'booking',
                    'remarks' => $request->remarks,
                ]);

                $uploadedImages[] = [
                    'id' => $pickupImage->id,
                    'url' => Storage::url($path),
                    'type' => $pickupImage->type
                ];
            }

            return $this->successResponse('images.uploaded', [
                'images' => $uploadedImages,
                'count' => count($uploadedImages)
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }

    /**
     * Get images for a pickup request.
     */
    public function getPickupImages($pickupRequestId)
    {
        $images = PickupImage::where('pickup_request_id', $pickupRequestId)
            ->get()
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => Storage::url($image->image_path),
                    'type' => $image->type,
                    'uploaded_at' => $image->created_at
                ];
            });

        return $this->successResponse('images.fetched', $images);
    }

    /**
     * Delete an image.
     */
    public function deleteImage($id)
    {
        $image = PickupImage::find($id);

        if (!$image) {
            return $this->errorResponse('image.not_found', 404);
        }

        try {
            // Delete file from storage
            Storage::disk('public')->delete($image->image_path);

            // Delete database record
            $image->delete();

            return $this->successResponse('image.deleted');

        } catch (\Exception $e) {
            return $this->errorResponse('server.error', 500, $e->getMessage());
        }
    }
}
