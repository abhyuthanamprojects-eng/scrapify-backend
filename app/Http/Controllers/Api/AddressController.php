<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class AddressController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/auth/profile/addresses",
        operationId: "getAddresses",
        tags: ["Addresses"],
        summary: "Get saved addresses",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->with('city')->get();
        return $this->successResponse('address.fetched', $addresses);
    }

    #[OA\Post(
        path: "/api/auth/profile/addresses",
        operationId: "storeAddress",
        tags: ["Addresses"],
        summary: "Add new address",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["address_line_1", "pincode", "city_id"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Home"),
                    new OA\Property(property: "address_line_1", type: "string"),
                    new OA\Property(property: "address_line_2", type: "string"),
                    new OA\Property(property: "pincode", type: "string"),
                    new OA\Property(property: "city_id", type: "integer"),
                    new OA\Property(property: "is_default", type: "boolean"),
                    new OA\Property(property: "latitude", type: "number"),
                    new OA\Property(property: "longitude", type: "number")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'address_line_1' => 'required|string',
            'address_line_2' => 'nullable|string',
            'pincode' => 'required|string|max:10',
            'city_id' => 'required|exists:cities,id',
            'state' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($request->is_default) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($request->all());

        return $this->successResponse('address.created', $address->load('city'), 201);
    }

    #[OA\Put(
        path: "/api/auth/profile/addresses/{id}",
        operationId: "updateAddress",
        tags: ["Addresses"],
        summary: "Update address",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Home"),
                    new OA\Property(property: "address_line_1", type: "string"),
                    new OA\Property(property: "address_line_2", type: "string"),
                    new OA\Property(property: "pincode", type: "string"),
                    new OA\Property(property: "city_id", type: "integer"),
                    new OA\Property(property: "is_default", type: "boolean"),
                    new OA\Property(property: "latitude", type: "number"),
                    new OA\Property(property: "longitude", type: "number")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated"),
            new OA\Response(response: 403, description: "Unauthorized")
        ]
    )]
    public function update(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'address_line_1' => 'sometimes|required|string',
            'address_line_2' => 'nullable|string',
            'pincode' => 'sometimes|required|string|max:10',
            'city_id' => 'sometimes|required|exists:cities,id',
            'state' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($request->is_default) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($request->all());

        return $this->successResponse('address.updated', $address->load('city'));
    }

    #[OA\Delete(
        path: "/api/auth/profile/addresses/{id}",
        operationId: "deleteAddress",
        tags: ["Addresses"],
        summary: "Delete address",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 403, description: "Unauthorized")
        ]
    )]
    public function destroy(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return $this->errorResponse('auth.unauthorized', 403);
        }

        $address->delete();

        return $this->successResponse('address.deleted');
    }
}
