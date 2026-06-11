<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Waitlist;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class WaitlistController extends Controller
{
    use ApiResponseTrait;

    #[OA\Post(
        path: "/api/v1/waitlist",
        operationId: "submitWaitlist",
        tags: ["Waitlist"],
        summary: "Join waitlist for unsupported locations",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "phone", "city"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Amit Sinha"),
                    new OA\Property(property: "phone", type: "string", example: "9876543210"),
                    new OA\Property(property: "email", type: "string", example: "amit@example.com"),
                    new OA\Property(property: "city", type: "string", example: "Gurugram"),
                    new OA\Property(property: "state", type: "string", example: "Haryana"),
                    new OA\Property(property: "location_name", type: "string", example: "Gurugram, Haryana"),
                    new OA\Property(property: "pincode", type: "string", example: "122001"),
                    new OA\Property(property: "latitude", type: "number", example: 28.4958),
                    new OA\Property(property: "longitude", type: "number", example: 77.0069),
                    new OA\Property(property: "message", type: "string", example: "Please launch service here.")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Waitlist request submitted successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[6-9]\d{9}$/',
            'email' => 'nullable|email|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'location_name' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $waitlist = Waitlist::create($validator->validated());

        return $this->successResponse('waitlist.submitted_success', $waitlist, 201);
    }
}
