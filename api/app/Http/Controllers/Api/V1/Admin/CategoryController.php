<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Category\ListCategoryRequest;
use App\Http\Requests\Api\V1\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\Admin\CategoryResource;
use App\Models\Category;
use App\Services\Api\V1\Admin\CategoryService;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/categories',
        tags: ['Admin/Categories'],
        summary: 'Get category list',
        description: 'Returns paginated categories for admin.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search_txt',
                in: 'query',
                required: false,
                description: 'Search by category name.',
                schema: new OA\Schema(type: 'string', maxLength: 255)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page.',
                schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 200)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Categories list'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(ListCategoryRequest $request)
    {
        $categories = $this->categoryService->paginate($request->validated());
        $categories->setCollection($categories->getCollection()->map(fn($category) => CategoryResource::make($category)->resolve()));

        return $this->paginate($categories, 'Categories list');
    }

    #[OA\Get(
        path: '/api/v1/admin/categories/{category}',
        tags: ['Admin/Categories'],
        summary: 'Get category detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'path',
                required: true,
                description: 'Category ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category detail'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function show(Category $category)
    {
        return $this->success(new CategoryResource($category), 'Category detail');
    }

    #[OA\Post(
        path: '/api/v1/admin/categories',
        tags: ['Admin/Categories'],
        summary: 'Create category',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Shirts'),
                        new OA\Property(property: 'des', type: 'string', maxLength: 1000, nullable: true, example: 'Shirt category'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Category created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryService->store($request->validated(), $request->file('image'));

        return $this->created(new CategoryResource($category), 'Category created');
    }

    #[OA\Put(
        path: '/api/v1/admin/categories/{category}',
        tags: ['Admin/Categories'],
        summary: 'Update category',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'path',
                required: true,
                description: 'Category ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Updated Shirts'),
                        new OA\Property(property: 'des', type: 'string', maxLength: 1000, nullable: true),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'remove_image', type: 'boolean', example: false),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Category updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category = $this->categoryService->update($category, $request->validated(), $request->file('image'));

        return $this->success(new CategoryResource($category->fresh()), 'Category updated');
    }

    #[OA\Delete(
        path: '/api/v1/admin/categories/{category}',
        tags: ['Admin/Categories'],
        summary: 'Delete category',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'category',
                in: 'path',
                required: true,
                description: 'Category ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category deleted'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function destroy(Category $category)
    {
        $this->categoryService->destroy($category);

        return $this->success(null, 'Category deleted');
    }
}
