<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Collection\ListCollectionRequest;
use App\Http\Requests\Api\V1\Admin\Collection\StoreCollectionRequest;
use App\Http\Requests\Api\V1\Admin\Collection\UpdateCollectionRequest;
use App\Http\Resources\Api\V1\Admin\CollectionResource;
use App\Models\Collection;
use App\Services\Api\V1\Admin\CollectionService;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class CollectionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CollectionService $collectionService)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/collections',
        tags: ['Admin/Collections'],
        summary: 'Get collection list',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Collections list'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(ListCollectionRequest $request)
    {
        $collections = $this->collectionService->paginate($request->validated());
        $collections->setCollection($collections->getCollection()->map(fn($collection) => CollectionResource::make($collection)->resolve()));

        return $this->paginate($collections, 'Collections list');
    }

    #[OA\Get(
        path: '/api/v1/admin/collections/{collection}',
        tags: ['Admin/Collections'],
        summary: 'Get collection detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'collection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Collection detail'),
            new OA\Response(response: 404, description: 'Collection not found'),
        ]
    )]
    public function show(Collection $collection)
    {
        $collection = $this->collectionService->show($collection);

        return $this->success(new CollectionResource($collection), 'Collection detail');
    }

    #[OA\Post(
        path: '/api/v1/admin/collections',
        tags: ['Admin/Collections'],
        summary: 'Create collection',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Collection created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreCollectionRequest $request)
    {
        $collection = $this->collectionService->store($request->validated(), $request->file('image'));

        return $this->created(new CollectionResource($collection), 'Collection created');
    }

    #[OA\Put(
        path: '/api/v1/admin/collections/{collection}',
        tags: ['Admin/Collections'],
        summary: 'Update collection',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'collection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Collection updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateCollectionRequest $request, Collection $collection)
    {
        $collection = $this->collectionService->update($collection, $request->validated(), $request->file('image'));

        return $this->success(new CollectionResource($collection), 'Collection updated');
    }

    #[OA\Delete(
        path: '/api/v1/admin/collections/{collection}',
        tags: ['Admin/Collections'],
        summary: 'Delete collection',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'collection', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Collection deleted'),
            new OA\Response(response: 404, description: 'Collection not found'),
        ]
    )]
    public function destroy(Collection $collection)
    {
        $this->collectionService->destroy($collection);

        return $this->success(null, 'Collection deleted');
    }
}
