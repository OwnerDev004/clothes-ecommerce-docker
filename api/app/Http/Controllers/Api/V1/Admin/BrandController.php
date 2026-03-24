<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Brand\ListBrandRequest;
use App\Http\Requests\Api\V1\Admin\Brand\StoreBrandRequest;
use App\Http\Requests\Api\V1\Admin\Brand\UpdateBrandRequest;
use App\Http\Resources\Api\V1\Admin\BrandResource;
use App\Models\Brand;
use App\Services\Api\V1\Admin\BrandService;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class BrandController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BrandService $brandService)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/brands',
        summary: 'Retrive all Brands',
        description: 'Returns paginated Brands for admin.',
        tags: ['Admin/Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search_txt',
                in: 'query',
                required: false,
                description: 'Search by brand name',
                schema: new OA\Schema(
                    type: 'string',
                    maxLength: 255
                )
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Item per page',
                schema: new OA\Schema(
                    type: 'integer',
                    default: 15,
                    minimum: 1,
                    maximum: 200
                )
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Brands list'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(ListBrandRequest $request)
    {
        $brands = $this->brandService->paginate($request->validated());
        $brands->setCollection($brands->getCollection()->map(fn($brand) => BrandResource::make($brand)->resolve()));

        return $this->paginate($brands, 'Brands list');
    }

    #[OA\Get(
        path: '/api/v1/admin/brands/{brand}',
        summary: 'Retrive Brands By BrandId',
        description: 'Returns Brands specifict ID for admin.',
        tags: ['Admin/Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'brand',
                in: 'path',
                required: true,
                description: 'Brand ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Brands detail'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(Brand $brand)
    {
        return $this->success(new BrandResource($brand), 'Brand detail');
    }

    #[OA\Post(
        path: "/api/v1/admin/brands",
        tags: ["Admin/Brands"],
        summary: "Create brands",
        security: [['bearerAuth' => []]],
        requestBody: (
            new OA\RequestBody(
                required: true,
                content: [
                    new OA\MediaType(
                        mediaType: 'application/json',
                        schema: new OA\Schema(
                            required: ['name'],
                            properties: [
                                new OA\Property(
                                    property: 'name',
                                    type: 'string',
                                    maxLength: 255,
                                    default: 'Zara'
                                ),
                                new OA\Property(
                                    property: 'sort_order',
                                    type: 'integer',
                                    nullable: true
                                )
                            ]
                        )
                    ),
                    new OA\MediaType(
                        mediaType: 'multipart/form-data',
                        schema: new OA\Schema(
                            required: ['name'],
                            properties: [
                                new OA\Property(
                                    property: 'name',
                                    type: 'string',
                                    maxLength: 255,
                                    default: 'Zara'
                                ),
                                new OA\Property(
                                    property: 'sort_order',
                                    type: 'integer',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'image',
                                    type: 'string',
                                    format: 'binary',
                                    nullable: true
                                )
                            ]
                        )

                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Brand Created Success'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreBrandRequest $request)
    {
        $brand = $this->brandService->store($request->validated(), $request->file('image'));

        return $this->created(new BrandResource($brand), 'Brand created');
    }

    #[OA\Put(
        path: '/api/v1/admin/brands/{brand}',
        summary: 'Update Brands',
        description: 'Update Brands by brand id',
        tags: ['Admin/Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'brand',
                description: 'Brand ID',
                required: true,
                in: 'path',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )

            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'name',
                            type: 'string',
                            maxLength: 255,
                            default: 'Zara'
                        ),
                        new OA\Property(
                            property: 'sort_order',
                            type: 'integer',
                            nullable: true
                        ),
                        new OA\Property(
                            property: 'image',
                            type: 'string',
                            format: 'binary',
                            nullable: true
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Brand has updated'),
            new OA\Response(response: 422, description: 'Validation false')
        ]
    )]
    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $brand = $this->brandService->update($brand, $request->validated(), $request->file('image'));

        return $this->success(new BrandResource($brand->fresh()), 'Brand updated');
    }

    #[OA\Delete(
        path: '/api/v1/admin/brands/{brand}',
        summary: 'Delete Brand by id',
        tags: ['Admin/Brands'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'brand',
                description: 'Brand ID',
                in: 'path',
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Brand Deleted'),
            new OA\Response(response: 404, description: 'Brand Not Founded')
        ]
    )]
    public function destroy(Brand $brand)
    {
        $this->brandService->destroy($brand);

        return $this->success(null, 'Brand deleted');
    }
}
