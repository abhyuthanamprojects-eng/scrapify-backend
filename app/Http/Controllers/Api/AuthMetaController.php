<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use OpenApi\Attributes as OA;

class AuthMetaController extends Controller
{
    use ApiResponseTrait;

    #[OA\Get(
        path: "/api/auth/user-types",
        operationId: "getUserTypes",
        tags: ["Auth"],
        summary: "Get available user types for login selection",
        responses: [
            new OA\Response(response: 200, description: "List of user types")
        ]
    )]
    public function userTypes()
    {
        $allowedRoles = ['customer', 'channel_partner', 'pickup_boy', 'warehouse'];
        // $allowedRoles = ['customer', 'pickup_boy', 'warehouse'];

        $roles = Role::whereIn('name', $allowedRoles)
            ->orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($role) {
                // Name formatting: "channel_partner" -> "Channel Partner"
                $displayName = ucwords(str_replace('_', ' ', $role->name));
                return [
                    'code' => $role->name,
                    'name' => $displayName,
                    'visible' => (bool) $role->visible,
                    'sort_order' => $role->sort_order,
                ];
            });

        return $this->successResponse('auth.user_types_fetched', $roles);
    }
}
