<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all category types.
     */
    #[OA\Get(
        path: "/api/categories",
        operationId: "getCategoryTypes",
        tags: ["Categories"],
        summary: "Get all category types",
        description: "Returns list of top-level category types (e.g. E-Waste, Metal Scrap) with their names and images.",
        security: [["apiAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Category types fetched successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean"),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "name", type: "string"),
                                    new OA\Property(property: "image", type: "string", format: "url")
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        // New 2-level flow: return top-level categories directly when requested.
        if (request()->boolean('use_tree')) {
            $topCategories = Category::with('pricingRules')
                ->withCount('attributes')
                ->whereNull('parent_id')
                ->where('status', true)
                ->get();

            $data = $topCategories->map(function ($category) {
                $rule = $category->pricingRules->firstWhere('attribute_option_id', null);
                return [
                    'id' => $category->id,
                    'name' => $category->getTranslatedName(),
                    'image' => $category->image_url,
                    'base_price' => $rule ? (float) $rule->base_price : 0,
                    'carbon_per_unit' => $rule && $rule->carbon_per_unit !== null ? (float) $rule->carbon_per_unit : 0,
                    'pricing_type' => $rule?->pricing_type ?? 'per_piece',
                    'requires_details' => (bool) ($category->requires_details ?? false) || ((int) ($category->attributes_count ?? 0)) > 0,
                ];
            });

            return $this->successResponse('categories.fetched', $data);
        }

        $types = \App\Models\CategoryType::where('status', true)->get();

        $data = $types->map(function ($type) {
            return [
                'id' => $type->id,
                'name' => $type->getTranslatedName(),
                'image' => $type->image_url,
            ];
        });

        return $this->successResponse('category_types.fetched', $data);
    }

    /**
     * Get subcategories for a specific category type.
     */
    #[OA\Get(
        path: "/api/subcategories",
        operationId: "getSubcategories",
        tags: ["Categories"],
        summary: "Get subcategories for a category type",
        description: "Returns the second-level categories (e.g. Computers & Laptops) for a given category type ID.",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "category_id",
                in: "query",
                description: "The ID of the category type",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Subcategories fetched successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean"),
                        new OA\Property(property: "category_id", type: "integer"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "name", type: "string"),
                                    new OA\Property(property: "image", type: "string", format: "url")
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]
    public function subcategories(Request $request)
    {
        // Primary flow: category_type_id -> subcategories.
        // Legacy fallback (only when explicitly requested): parent category id -> children.
        $validated = $request->validate([
            'category_id' => 'required|integer',
            'parent_mode' => 'nullable|boolean',
        ]);

        $categoryId = (int) $validated['category_id'];
        $useParentMode = (bool) ($validated['parent_mode'] ?? false);

        if ($useParentMode) {
            $categories = Category::with('pricingRules')
                ->withCount('attributes')
                ->where('parent_id', $categoryId)
                ->where('status', true)
                ->get();
        } else {
            $categories = Category::with('pricingRules')
                ->withCount('attributes')
                ->where('category_type_id', $categoryId)
                ->whereNull('parent_id')
                ->where('status', true)
                ->get();
        }

        $data = $categories->map(function ($cat) {
            $rule = $cat->pricingRules->firstWhere('attribute_option_id', null);
            return [
                'id' => $cat->id,
                'name' => $cat->getTranslatedName(),
                'image' => $cat->image_url,
                'base_price' => $rule ? (float) $rule->base_price : 0,
                'carbon_per_unit' => $rule && $rule->carbon_per_unit !== null ? (float) $rule->carbon_per_unit : 0,
                'pricing_type' => $rule?->pricing_type ?? 'per_piece',
                'requires_details' => (bool) ($cat->requires_details ?? false) || ((int) ($cat->attributes_count ?? 0)) > 0,
            ];
        });

        return $this->successResponse('subcategories.fetched', [
            'id' => $categoryId,
            'items' => $data
        ]);
    }

    /**
     * Get single category details.
     */
    #[OA\Get(
        path: "/api/categories/{id}",
        operationId: "getCategoryDetail",
        tags: ["Categories"],
        summary: "Get specific category details",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Category fetched"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show($id)
    {
        $category = Category::with(['children', 'attributes.options', 'pricingRules'])
            ->where('status', true)
            ->find($id);

        if (!$category) {
            return $this->errorResponse('category.not_found', 404);
        }

        return $this->successResponse('category.fetched', $category);
    }
}
