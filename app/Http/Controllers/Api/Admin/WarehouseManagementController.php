<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Warehouse;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class WarehouseManagementController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/admin/warehouses",
        operationId: "adminListWarehouses",
        tags: ["Admin"],
        summary: "List all warehouses with filters",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = Warehouse::with('city');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $warehouses = $query->paginate($request->per_page ?? 20);

        return $this->paginatedResponse('admin.warehouses_fetched', $warehouses);
    }

    #[OA\Post(
        path: "/api/admin/warehouses",
        operationId: "adminStoreWarehouse",
        tags: ["Admin"],
        summary: "Create a new warehouse",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "address", "city_id"],
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "address", type: "string"),
                    new OA\Property(property: "latitude", type: "number"),
                    new OA\Property(property: "longitude", type: "number"),
                    new OA\Property(property: "service_pincodes", type: "array", items: new OA\Items(type: "string")),
                    new OA\Property(property: "city_id", type: "integer"),
                    // area and zone can be added as needed
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Warehouse created")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'service_pincodes' => 'nullable|array|max:' . max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10)),
            'service_pincodes.*' => 'nullable|string|regex:/^\d{6}$/',
            'city_id' => 'required|exists:cities,id',
            'area' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'nullable|boolean',
            'accepts_corporate' => 'nullable|boolean',
            'accepts_donation' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $data = $validator->validated();
        $data['service_pincodes'] = Warehouse::normalizePincodeList(
            $request->input('service_pincodes', []),
            max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10))
        );

        $duplicatePincodes = Warehouse::duplicateServicePincodes($data['service_pincodes']);
        if (!empty($duplicatePincodes)) {
            return $this->validationErrorResponse([
                'service_pincodes' => [$this->duplicatePincodeMessage($duplicatePincodes)],
                'duplicates' => $duplicatePincodes,
            ]);
        }

        $warehouse = Warehouse::create($data);

        return $this->itemCreatedResponse('admin.warehouse_created', $warehouse);
    }

    #[OA\Get(
        path: "/api/admin/warehouses/{id}",
        operationId: "adminShowWarehouse",
        tags: ["Admin"],
        summary: "Get warehouse details",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function show($id)
    {
        $warehouse = Warehouse::with(['city', 'manager'])->find($id);

        if (!$warehouse) {
            return $this->errorResponse('general.not_found', 404);
        }

        return $this->successResponse('admin.warehouse_fetched', $warehouse);
    }

    #[OA\Put(
        path: "/api/admin/warehouses/{id}",
        operationId: "adminUpdateWarehouse",
        tags: ["Admin"],
        summary: "Update warehouse details",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "status", type: "boolean"),
                    new OA\Property(property: "service_pincodes", type: "array", items: new OA\Items(type: "string")),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Warehouse updated")
        ]
    )]
    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->errorResponse('general.not_found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'service_pincodes' => 'sometimes|array|max:' . max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10)),
            'service_pincodes.*' => 'nullable|string|regex:/^\d{6}$/',
            'city_id' => 'sometimes|exists:cities,id',
            'area' => 'sometimes|string|max:255',
            'zone' => 'sometimes|string|max:255',
            'manager_id' => 'sometimes|nullable|exists:users,id',
            'status' => 'sometimes|boolean',
            'accepts_corporate' => 'sometimes|boolean',
            'accepts_donation' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $data = $validator->validated();
        if ($request->has('service_pincodes')) {
            $data['service_pincodes'] = Warehouse::normalizePincodeList(
                $request->input('service_pincodes', []),
                max(1, (int) AppSetting::get('warehouse_service_pincodes_limit', 10))
            );

            $duplicatePincodes = Warehouse::duplicateServicePincodes($data['service_pincodes'], $warehouse->id);
            if (!empty($duplicatePincodes)) {
                return $this->validationErrorResponse([
                    'service_pincodes' => [$this->duplicatePincodeMessage($duplicatePincodes)],
                    'duplicates' => $duplicatePincodes,
                ]);
            }
        }

        $warehouse->update($data);

        return $this->successResponse('admin.warehouse_updated', $warehouse);
    }

    protected function duplicatePincodeMessage(array $duplicates): string
    {
        $items = collect($duplicates)
            ->map(fn ($warehouse, $pincode) => "{$pincode} is already assigned to {$warehouse['warehouse_name']}")
            ->values()
            ->all();

        return 'Each service pincode can be assigned to only one warehouse. ' . implode('; ', $items) . '.';
    }

    #[OA\Delete(
        path: "/api/admin/warehouses/{id}",
        operationId: "adminDeleteWarehouse",
        tags: ["Admin"],
        summary: "Delete warehouse",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Warehouse deleted")
        ]
    )]
    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return $this->errorResponse('general.not_found', 404);
        }

        $warehouse->delete();

        return $this->successResponse('admin.warehouse_deleted', null);
    }
}
