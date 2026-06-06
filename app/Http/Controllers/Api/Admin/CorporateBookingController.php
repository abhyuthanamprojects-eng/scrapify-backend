<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PickupRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CorporateBookingController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/admin/corporate-bookings",
        operationId: "adminGetCorporateBookings",
        tags: ["Admin Corporate"],
        summary: "List corporate pickup requests for admin",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of corporate bookings")
        ]
    )]
    public function index(Request $request)
    {
        $query = PickupRequest::where('request_type', 'corporate')
            ->with(['items', 'images', 'customer'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->whereIn('status', explode(',', $request->status));
        }

        $requests = $query->paginate($request->per_page ?? 20);
        return $this->paginatedResponse('admin.corporate.fetched', $requests);
    }

    #[OA\Post(
        path: "/api/admin/corporate-bookings/{id}/quote",
        operationId: "adminQuoteCorporateBooking",
        tags: ["Admin Corporate"],
        summary: "Provide a quote for a corporate booking",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Quote provided successfully")
        ]
    )]
    public function quote(Request $request, $id)
    {
        $request->validate([
            'estimated_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        $booking = PickupRequest::where('request_type', 'corporate')->findOrFail($id);
        
        $metadata = $booking->metadata ?? [];
        $metadata['quoted_at'] = now()->toDateTimeString();
        $metadata['quoted_by'] = optional($request->user())->id;
        if ($request->has('notes')) {
            $metadata['admin_quote_notes'] = $request->notes;
        }

        $booking->update([
            'estimated_amount' => $request->estimated_amount,
            'metadata' => $metadata,
        ]);

        return $this->successResponse('admin.corporate.quote_updated', $booking);
    }
}
