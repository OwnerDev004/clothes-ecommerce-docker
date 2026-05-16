<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\ProductFilterRequest;
use App\Http\Requests\Api\V1\Product\ProductStoreRequest;
use App\Http\Requests\Api\V1\Product\ProductUpdateRequest;
use App\Repositories\Admin\ProductRepository;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;
class ProductController extends Controller
{
    use ApiResponse;
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/v1/admin/products',
        tags: ['Admin/Products'],
        summary: 'Get products',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Products list'),
        ]
    )]
    public function index(ProductFilterRequest $request)
    {
        $filters = $request->validated();

        $products = $this->productRepository->getAll($filters);
        return $this->paginate($products, "success get products");
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/v1/admin/products',
        tags: ['Admin/Products'],
        summary: 'Create product',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'category_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'desc', type: 'string', nullable: true),
                    new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'sub_category_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'brand_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'collection_ids', type: 'array', nullable: true, items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(ProductStoreRequest $request)
    {
        $products = $this->productRepository->storeProduct($request->validated());

        return $this->success($products, "success created product.", 200);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/v1/admin/products/{id}',
        tags: ['Admin/Products'],
        summary: 'Get product detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product detail'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return $this->error("Product not exist", 404);
        }
        return $this->success($product, "success filter Product", 200);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/v1/admin/products/{id}',
        tags: ['Admin/Products'],
        summary: 'Update product',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'desc', type: 'string', nullable: true),
                    new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'sub_category_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'brand_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'collection_ids', type: 'array', nullable: true, items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'clear_images', type: 'boolean', nullable: true),
                    new OA\Property(
                        property: 'existing_images',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'image_type', type: 'string', nullable: true, enum: ['thumbnail', 'gallery']),
                                new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
                            ]
                        )
                    ),
                    new OA\Property(
                        property: 'new_images',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'file', type: 'string', format: 'binary'),
                                new OA\Property(property: 'image_type', type: 'string', nullable: true, enum: ['thumbnail', 'gallery']),
                                new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(ProductUpdateRequest $request, int $id)
    {
        $payload = $request->validated();

        // Preserve explicit image intent from multipart/form-data even when files are empty.
        if ($request->exists('new_images') && !array_key_exists('new_images', $payload)) {
            $payload['new_images'] = [];
        }
        if ($request->exists('existing_images') && !array_key_exists('existing_images', $payload)) {
            $payload['existing_images'] = [];
        }
        if ($request->exists('clear_images') && !array_key_exists('clear_images', $payload)) {
            $payload['clear_images'] = $request->boolean('clear_images');
        }

        $product = $this->productRepository->updateProduct($id, $payload);

        return $this->success($product, "success updated product.", 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/v1/admin/products/{id}',
        tags: ['Admin/Products'],
        summary: 'Delete product',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product deleted'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function destroy(int $id)
    {
        $this->productRepository->deleteProduct($id); // throws 404 if not found
        return $this->success(null, "Product deleted", 200);
    }
}
