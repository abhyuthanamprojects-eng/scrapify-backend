<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentDetail;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class PaymentDetailController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/auth/profile/payment-details",
        operationId: "getPaymentDetails",
        tags: ["Payment Details"],
        summary: "Get saved payment details",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $payments = $request->user()->paymentDetails;
        return $this->successResponse('payment_details.fetched', $payments);
    }

    #[OA\Post(
        path: "/api/auth/profile/payment-details",
        operationId: "storePaymentDetail",
        tags: ["Payment Details"],
        summary: "Add new payment detail",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["type"],
                properties: [
                    new OA\Property(property: "type", type: "string", enum: ["bank", "upi"]),
                    new OA\Property(property: "bank_name", type: "string"),
                    new OA\Property(property: "account_number", type: "string"),
                    new OA\Property(property: "ifsc_code", type: "string"),
                    new OA\Property(property: "account_holder_name", type: "string"),
                    new OA\Property(property: "upi_id", type: "string"),
                    new OA\Property(property: "is_default", type: "boolean")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Added"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bank,upi',
            'bank_name' => 'required_if:type,bank|nullable|string',
            'account_number' => 'required_if:type,bank|nullable|string',
            'ifsc_code' => 'required_if:type,bank|nullable|string',
            'account_holder_name' => 'required_if:type,bank|nullable|string',
            'upi_id' => 'required_if:type,upi|nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->is_default) {
            $request->user()->paymentDetails()->update(['is_default' => false]);
        }

        $payment = $request->user()->paymentDetails()->create($validated);

        return $this->successResponse('payment_details.added', $payment, 201);
    }

    #[OA\Put(
        path: "/api/auth/profile/payment-details/{id}",
        operationId: "updatePaymentDetail",
        tags: ["Payment Details"],
        summary: "Update payment detail",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "type", type: "string", enum: ["bank", "upi"]),
                    new OA\Property(property: "bank_name", type: "string"),
                    new OA\Property(property: "account_number", type: "string"),
                    new OA\Property(property: "ifsc_code", type: "string"),
                    new OA\Property(property: "account_holder_name", type: "string"),
                    new OA\Property(property: "upi_id", type: "string"),
                    new OA\Property(property: "is_default", type: "boolean")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated"),
            new OA\Response(response: 403, description: "Unauthorized")
        ]
    )]
    public function update(Request $request, PaymentDetail $paymentDetail)
    {
        if ($paymentDetail->user_id !== $request->user()->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        $validated = $request->validate([
            'type' => 'sometimes|required|in:bank,upi',
            'bank_name' => 'required_if:type,bank|nullable|string',
            'account_number' => 'required_if:type,bank|nullable|string',
            'ifsc_code' => 'required_if:type,bank|nullable|string',
            'account_holder_name' => 'required_if:type,bank|nullable|string',
            'upi_id' => 'required_if:type,upi|nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        if ($request->is_default) {
            $request->user()->paymentDetails()->update(['is_default' => false]);
        }

        $paymentDetail->update($validated);

        return $this->successResponse('payment_details.updated', $paymentDetail);
    }

    #[OA\Delete(
        path: "/api/auth/profile/payment-details/{id}",
        operationId: "deletePaymentDetail",
        tags: ["Payment Details"],
        summary: "Delete payment detail",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 403, description: "Unauthorized")
        ]
    )]
    public function destroy(Request $request, PaymentDetail $paymentDetail)
    {
        if ($paymentDetail->user_id !== $request->user()->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        $paymentDetail->delete();

        return $this->successResponse('payment_details.deleted');
    }
}
