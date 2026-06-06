<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all categories with sub-categories.
     */
    public function index()
    {
        // Fetch only parent categories with their active children
        $categories = Category::whereNull('parent_id')
            ->where('status', true)
            ->with([
                'children' => function ($query) {
                    $query->where('status', true);
                }
            ])
            ->get();

        return $this->successResponse('general.success', $categories);
    }

    /**
     * Get specific category details (with attributes).
     */
    public function show($id)
    {
        $category = Category::with(['attributes.options', 'children'])
            ->where('status', true)
            ->find($id);

        if (!$category) {
            return $this->errorResponse('general.not_found', 404);
        }

        return $this->successResponse('general.success', $category);
    }
}
