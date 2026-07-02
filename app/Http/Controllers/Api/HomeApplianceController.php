<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\AttributeOption;
use App\Services\HomeAppliancePricingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class HomeApplianceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly HomeAppliancePricingService $pricingService)
    {
    }

    /**
     * Get detailed attributes and pricing for Home Appliances.
     */
    #[OA\Get(
        path: "/api/home-appliances/details",
        operationId: "getHomeApplianceDetails",
        tags: ["Home Appliances"],
        summary: "Get structured details for specific home appliance category",
        description: "Returns attributes (Brands, Capacity, Types) and base pricing specifically for categories like AC and Microwave.",
        security: [["apiAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "category_id",
                in: "query",
                description: "The ID of the category (e.g. Air Conditioner or Microwave)",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Details fetched successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean"),
                        new OA\Property(property: "message", type: "string"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "id", type: "integer"),
                                new OA\Property(property: "name", type: "string"),
                                new OA\Property(property: "estimated_price", type: "number"),
                                new OA\Property(property: "sections", type: "array", items: new OA\Items(
                                    properties: [
                                        new OA\Property(property: "title", type: "string"),
                                        new OA\Property(property: "slug", type: "string"),
                                        new OA\Property(property: "options", type: "array", items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: "id", type: "integer"),
                                                new OA\Property(property: "value", type: "string")
                                            ]
                                        ))
                                    ]
                                ))
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    public function details(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $category = Category::with(['attributes.options', 'pricingRules'])
            ->find($request->category_id);

        if (!$category) {
            return $this->errorResponse('category.not_found', 404);
        }

        $pricing = $category->pricingRules->first();
        $carbonRule = $category->pricingRules->firstWhere('attribute_option_id', null);
        
        $sections = $category->attributes->map(function ($attr) {
            return [
                'id' => $attr->id,
                'title' => $attr->name['en'] ?? $attr->name,
                'slug' => $attr->slug,
                'options' => $attr->options->map(function ($opt) {
                    return [
                        'id' => $opt->id,
                        'value' => $opt->value['en'] ?? $opt->value,
                    ];
                }),
            ];
        });

        $variantRules = Schema::hasTable('pricing_variant_rules')
            ? $category->variantPricingRules()
                ->where('status', true)
                ->orderBy('id')
                ->get()
                ->map(fn($rule) => [
                    'id' => $rule->id,
                    'title' => $rule->title,
                    'base_price' => (float) $rule->base_price,
                    'option_values' => $rule->option_values ?? [],
                    'source_column' => $rule->source_column,
                ])
                ->values()
            : collect();

        $data = [
            'id' => $category->id,
            'name' => $category->name['en'] ?? $category->name,
            'estimated_price' => $pricing ? (float)$pricing->base_price : 0,
            'carbon_per_unit' => $carbonRule && $carbonRule->carbon_per_unit !== null ? (float) $carbonRule->carbon_per_unit : 0,
            'pricing_type' => $pricing?->pricing_type ?? 'per_piece',
            'sections' => $sections,
            'variant_pricing_rules' => $variantRules,
        ];

        return $this->successResponse('home_appliance_details.fetched', $data);
    }

    #[OA\Post(
        path: "/api/home-appliances/estimate",
        operationId: "estimateHomeAppliancePrice",
        tags: ["Home Appliances"],
        summary: "Estimate home appliance price based on selected attributes",
        security: [["apiAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_id", "attributes"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer"),
                    new OA\Property(
                        property: "attributes",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "attribute_id", type: "integer"),
                                new OA\Property(property: "option_id", type: "integer"),
                                new OA\Property(property: "value", type: "string")
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Estimate calculated"),
            new OA\Response(response: 422, description: "Invalid attribute selection")
        ]
    )]
    public function estimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'attributes' => 'required|array|min:1',
            'attributes.*.attribute_id' => 'nullable|integer|min:0',
            'attributes.*.option_id' => 'nullable|exists:attribute_options,id',
            'attributes.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $category = Category::with(['attributes.options', 'pricingRules'])
            ->find($request->integer('category_id'));

        if (!$category) {
            return $this->errorResponse('category.not_found', 404);
        }

        $categoryAttributeIds = $category->attributes->pluck('id')->all();
        $attributePayload = collect($request->input('attributes', []));

        $optionIds = $attributePayload
            ->pluck('option_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        // Validate attribute/option integrity. Supports legacy payload with attribute_id=0.
        foreach ($attributePayload as $row) {
            $optionId = isset($row['option_id']) ? (int) $row['option_id'] : null;
            $attributeId = isset($row['attribute_id']) ? (int) $row['attribute_id'] : 0;

            if ($attributeId > 0 && !in_array($attributeId, $categoryAttributeIds, true)) {
                return $this->errorResponse('home_appliance.invalid_attribute_selection', 422, [
                    'attributes' => ['Attribute does not belong to category'],
                ]);
            }

            if (!$optionId) {
                continue;
            }

            /** @var AttributeOption|null $option */
            $option = AttributeOption::find($optionId);
            if (!$option) {
                return $this->errorResponse('home_appliance.invalid_attribute_selection', 422, [
                    'attributes' => ['Option does not exist'],
                ]);
            }

            $resolvedAttributeId = $attributeId > 0 ? $attributeId : (int) $option->attribute_id;
            if (!in_array($resolvedAttributeId, $categoryAttributeIds, true)) {
                return $this->errorResponse('home_appliance.invalid_attribute_selection', 422, [
                    'attributes' => ['Option does not belong to category'],
                ]);
            }

            if ((int) $option->attribute_id !== $resolvedAttributeId) {
                return $this->errorResponse('home_appliance.invalid_attribute_selection', 422, [
                    'attributes' => ['Option does not belong to selected attribute'],
                ]);
            }
        }

        $estimateMeta = $this->pricingService->estimateWithMeta($category->id, $optionIds);
        $estimate = (float) $estimateMeta['estimated_price'];
        $pricingType = $estimateMeta['pricing_type'] ?? 'per_piece';

        return $this->successResponse('home_appliance.estimate_calculated', [
            'estimated_price' => $estimate,
            'price' => $estimate, // Backward compatibility fallback
            'carbon_per_unit' => (float) ($estimateMeta['carbon_per_unit'] ?? 0),
            'pricing_type' => $pricingType,
            'variant_rule' => $estimateMeta['variant_rule'] ?? null,
        ]);
    }

}
