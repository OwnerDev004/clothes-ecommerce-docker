<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    use ApiResponse;

    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/v1/categories',
        tags: ['Categories'],
        summary: 'Get categories',
        parameters: [
            new OA\Parameter(
                name: 'with_product_count',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Categories list'),
        ]
    )]
    public function index(Request $request)
    {
        $withProductCount = $request->boolean('with_product_count', false);

        $categories = $this->categoryRepository->getAll($withProductCount);

        return $this->success($categories, 'Categories list');
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/v1/categories/{category}',
        tags: ['Categories'],
        summary: 'Get category detail',
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'path',
                required: true,
                description: 'Category slug',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category detail'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function show(Category $category)
    {
        return $this->success(
            $category->only(['id', 'name', 'slug', 'des', 'image_url']),
            'Category detail'
        );
    }
}
