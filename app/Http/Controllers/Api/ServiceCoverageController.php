<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ServiceCoverageController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/v1/service-coverage",
        operationId: "getServiceCoverage",
        tags: ["Location"],
        summary: "List cities and areas covered by active warehouses",
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index()
    {
        $cities = City::whereHas('warehouses', function ($query) {
            $query->where('status', true);
        })
        ->with(['state', 'warehouses' => function ($query) {
            $query->where('status', true);
        }])
        ->get()
        ->map(function ($city) {
            $areas = $city->warehouses->groupBy('area')->map(function ($warehouses, $area) {
                return [
                    'area_name' => $area ?? 'General',
                    'warehouse_ids' => $warehouses->pluck('id'),
                ];
            })->values();

            return [
                'city_id' => $city->id,
                'city_name' => $city->name,
                'state_name' => $city->state->name ?? null,
                'warehouse_count' => $city->warehouses->count(),
                'areas' => $areas,
            ];
        });

        return $this->successResponse('location.coverage_fetched', ['cities' => $cities]);
    }
}
