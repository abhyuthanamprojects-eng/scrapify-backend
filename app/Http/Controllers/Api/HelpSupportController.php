<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\PickupRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class HelpSupportController extends Controller
{
    use ApiResponseTrait;

    #[OA\Post(
        path: "/api/help-support",
        operationId: "submitHelpSupport",
        tags: ["Help & Support"],
        summary: "Submit a help/support ticket (general or order-specific)",
        description: "Pass order id in `X-Order-Id` header (or `order_id` body field) for order-specific tickets. Works for customer, channel_partner, warehouse, pickup_boy roles.",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "X-Order-Id", in: "header", required: false, schema: new OA\Schema(type: "integer"), description: "Pickup/Donation/Corporate request ID")
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["message", "phone"],
                properties: [
                    new OA\Property(property: "subject", type: "string", example: "Issue with pickup"),
                    new OA\Property(property: "message", type: "string", example: "Need help with my order"),
                    new OA\Property(property: "phone", type: "string", example: "9999999999"),
                    new OA\Property(property: "order_id", type: "integer", example: 12, description: "Optional, alternative to header")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Ticket Created"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'phone'   => 'required|string|regex:/^[6-9]\d{9}$/',
            'order_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $orderId = $request->header('X-Order-Id') ?? $request->input('order_id');

        if ($orderId) {
            $exists = PickupRequest::where('id', $orderId)->exists();
            if (!$exists) {
                return $this->errorResponse('order.not_found', 404);
            }
        }

        $role = $user->roles()->pluck('name')->first();

        $ticket = ContactMessage::create([
            'user_id'           => $user->id,
            'pickup_request_id' => $orderId,
            'user_role'         => $role,
            'type'              => $orderId ? 'order' : 'general',
            'name'              => $user->name,
            'email'             => $user->email,
            'phone'             => $request->input('phone', $user->phone ?? null),
            'subject'           => $request->subject,
            'message'           => $request->message,
            'status'            => 'pending',
        ]);

        return $this->successResponse('help_support.submitted', $ticket, 201);
    }

    #[OA\Get(
        path: "/api/help-support",
        operationId: "listHelpSupport",
        tags: ["Help & Support"],
        summary: "List my help/support tickets",
        security: [["apiAuth" => []]],
        responses: [new OA\Response(response: 200, description: "OK")]
    )]
    public function index(Request $request)
    {
        $tickets = ContactMessage::where('user_id', Auth::id())
            ->with('pickupRequest:id,request_type,status,scheduled_at')
            ->latest()
            ->paginate($request->input('per_page', 15));

        return $this->successResponse('help_support.list', $tickets);
    }
}
