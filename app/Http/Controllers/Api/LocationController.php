<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\City;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class LocationController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/states",
        operationId: "getStates",
        tags: ["Location"],
        summary: "List all states",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function states()
    {
        $states = State::where('status', true)->get();
        return $this->successResponse('states.fetched', $states);
    }

    #[OA\Get(
        path: "/api/cities",
        operationId: "getCities",
        tags: ["Location"],
        summary: "List cities by state",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "state_id", in: "query", required: false, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function cities(Request $request)
    {
        $query = City::where('status', true);

        if ($request->has('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        $cities = $query->with('state')->get();
        return $this->successResponse('cities.fetched', $cities);
    }

    #[OA\Get(
        path: "/api/serviceable-cities",
        operationId: "getServiceableCities",
        tags: ["Location"],
        summary: "List all serviceable cities",
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function serviceableCities()
    {
        $cities = City::where('status', true)->with('state')->get();
        return $this->successResponse('location.serviceable_cities', $cities);
    }

    #[OA\Get(
        path: "/api/pickup-slots",
        operationId: "getPickupSlots",
        tags: ["Location"],
        summary: "Get available pickup slots",
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function pickupSlots(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'city_id' => 'nullable|integer|exists:cities,id',
            'pincode' => 'nullable|string|max:10',
        ]);

        $requestedDate = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m-d', $validated['date'])
            : now();

        $slots = [
            ['id' => 1, 'label' => '10:00 AM - 01:00 PM', 'available' => true, 'capacity_left' => 10],
            ['id' => 2, 'label' => '02:00 PM - 05:00 PM', 'available' => true, 'capacity_left' => 10],
        ];

        if ($requestedDate->isToday()) {
            $now = now();
            if ($now->hour >= 13) {
                $slots[0]['available'] = false;
                $slots[0]['capacity_left'] = 0;
            }
            if ($now->hour >= 17) {
                $slots[1]['available'] = false;
                $slots[1]['capacity_left'] = 0;
            }
        }

        return $this->successResponse('location.slots_fetched', [
            'date' => $requestedDate->toDateString(),
            'city_id' => $validated['city_id'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'slots' => $slots,
        ]);
    }
}
