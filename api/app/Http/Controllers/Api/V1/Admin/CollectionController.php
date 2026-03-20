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

class CollectionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CollectionService $collectionService)
    {
    }

    public function index(ListCollectionRequest $request)
    {
        $collections = $this->collectionService->paginate($request->validated());
        $collections->setCollection($collections->getCollection()->map(fn($collection) => CollectionResource::make($collection)->resolve()));

        return $this->paginate($collections, 'Collections list');
    }

    public function show(Collection $collection)
    {
        $collection = $this->collectionService->show($collection);

        return $this->success(new CollectionResource($collection), 'Collection detail');
    }

    public function store(StoreCollectionRequest $request)
    {
        $collection = $this->collectionService->store($request->validated(), $request->file('image'));

        return $this->created(new CollectionResource($collection), 'Collection created');
    }

    public function update(UpdateCollectionRequest $request, Collection $collection)
    {
        $collection = $this->collectionService->update($collection, $request->validated(), $request->file('image'));

        return $this->success(new CollectionResource($collection), 'Collection updated');
    }

    public function destroy(Collection $collection)
    {
        $this->collectionService->destroy($collection);

        return $this->success(null, 'Collection deleted');
    }
}

