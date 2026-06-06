<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AdminUserTypeController extends Controller
{
    use ApiResponseTrait;

    #[OA\Patch(
        path: "/api/admin/user-types/{code}/visibility",
        operationId: "updateUserTypeVisibility",
        tags: ["Admin User Types"],
        summary: "Toggle visibility of a user type in login screen",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "code", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["visible"],
                properties: [
                    new OA\Property(property: "visible", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Visibility updated"),
            new OA\Response(response: 404, description: "Role not found")
        ]
    )]
    public function updateVisibility(Request $request, $code)
    {
        $request->validate([
            'visible' => 'required|boolean'
        ]);

        $allowedRoles = ['customer', 'channel_partner', 'pickup_boy', 'warehouse'];

        if (!in_array($code, $allowedRoles)) {
            return $this->errorResponse('admin.invalid_role_code', 400);
        }

        $updated = DB::table('roles')->where('name', $code)->update([
            'visible' => $request->visible
        ]);

        if (!$updated && !DB::table('roles')->where('name', $code)->exists()) {
            return $this->errorResponse('admin.role_not_found', 404);
        }

        return $this->successResponse('admin.user_type_visibility_updated', [
            'code' => $code,
            'visible' => (bool)$request->visible
        ]);
    }
}
