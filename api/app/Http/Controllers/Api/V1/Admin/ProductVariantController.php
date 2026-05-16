<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductVariant\ProductVariantFilterRequest;
use App\Http\Requests\Api\V1\ProductVariant\ProductVariantStoreRequest;
use App\Http\Requests\Api\V1\ProductVariant\ProductVariantUpdateRequest;
use App\Models\ProductVariant;
use App\Repositories\Admin\ProductVariantRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductVariantController extends Controller
{
    use ApiResponse;
    protected $productVariantRepository;


    public function __construct(ProductVariantRepository $productVariantRepo)
    {
        $this->productVariantRepository = $productVariantRepo;
    }
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/v1/admin/product_variants',
        tags: ['Admin/Product Variants'],
        summary: 'Get product variants',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Product variants list'),
        ]
    )]
    public function index(ProductVariantFilterRequest $request)
    {
        $filters = $request->validated();
        $variants = $this->productVariantRepository->getAll($filters);
        return $this->success($variants, 'Product Variant success get', 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/v1/admin/product_variants',
        tags: ['Admin/Product Variants'],
        summary: 'Create product variant',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id', 'sell_price', 'cost_price'],
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer'),
                    new OA\Property(property: 'sku', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'color', type: 'string', maxLength: 64, nullable: true),
                    new OA\Property(property: 'size_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'stock_quantity', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'sell_price', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'cost_price', type: 'number', format: 'float', minimum: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product variant created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(ProductVariantStoreRequest $request)
    {
        $products = $this->productVariantRepository->storeProduct($request->validated());

        return $this->success("success creadted product.", $products, 200);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/v1/admin/product_variants/{id}',
        tags: ['Admin/Product Variants'],
        summary: 'Get product variant detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product variant detail'),
            new OA\Response(response: 404, description: 'Product variant not found'),
        ]
    )]
    public function show(int $id)
    {
        $variant = $this->productVariantRepository->findById($id);
        if (!$variant) {
            return $this->error("Product Variant not exist", 404);
        }
        return $this->success("success filter Product Variant", $variant, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/v1/admin/product_variants/{id}',
        tags: ['Admin/Product Variants'],
        summary: 'Update product variant',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer'),
                    new OA\Property(property: 'sku', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'color', type: 'string', maxLength: 64, nullable: true),
                    new OA\Property(property: 'size_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'stock_quantity', type: 'integer', minimum: 0, nullable: true),
                    new OA\Property(property: 'sell_price', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'cost_price', type: 'number', format: 'float', minimum: 0),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product variant updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(ProductVariantUpdateRequest $request, $id)
    {
        $payload = $request->validated();
        $productVariant = $this->productVariantRepository->updateVariant($id, $payload);
        return $this->success("Updated Product Variant Success", $productVariant, 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/v1/admin/product_variants/{id}',
        tags: ['Admin/Product Variants'],
        summary: 'Delete product variant',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product variant deleted'),
            new OA\Response(response: 404, description: 'Product variant not found'),
        ]
    )]
    public function destroy(int $id)
    {
        $this->productVariantRepository->deleteVariant($id); // throws 404 if not found
        return $this->success("Product Variant deleted", null, 200);
    }
}
