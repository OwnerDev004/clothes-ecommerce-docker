<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class CollectionController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/v1/collections',
        tags: ['Collections'],
        summary: 'Get collections',
        responses: [
            new OA\Response(response: 200, description: 'Collection list'),
        ]
    )]
    public function index()
    {
        $collections = Collection::query()
            ->select('id', 'category_id', 'name', 'slug', 'sort_order', 'img', 'image_url')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success($collections, 'Collection list');
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/v1/collections/{collection}',
        tags: ['Collections'],
        summary: 'Get collection detail',
        parameters: [
            new OA\Parameter(
                name: 'collection',
                in: 'path',
                required: true,
                description: 'Collection slug',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Collection detail'),
            new OA\Response(response: 404, description: 'Collection not found'),
        ]
    )]
    public function show(Collection $collection)
    {
        return $this->success($collection, 'Collections detail');
    }
}
