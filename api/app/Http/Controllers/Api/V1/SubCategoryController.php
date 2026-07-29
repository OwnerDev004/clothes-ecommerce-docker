<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubCategoryController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/sub-categories',
        tags: ['Sub Categories'],
        summary: 'Get sub categories',
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'query',
                required: false,
                description: 'Category id or slug',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sub categories list'),
        ]
    )]
    public function index(Request $request)
    {
        $category = $request->query('category');

        $subCategories = SubCategory::query()
            ->select('id', 'category_id', 'parent_id', 'name', 'slug', 'des', 'image_url', 'level', 'status')
            ->with('category:id,name,slug')
            ->where('status', true)
            ->when($category, function ($query) use ($category) {
                if (is_numeric($category)) {
                    $query->where('category_id', (int) $category);
                } else {
                    $query->whereHas('category', function ($q) use ($category) {
                        $q->where('slug', $category);
                    });
                }
            })
            ->orderBy('name')
            ->get();

        return $this->success($subCategories, 'Sub categories list');
    }
}
