<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Traits\ApiResponse;

class CollectionController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
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
    public function show(Collection $collection)
    {
        return $this->success($collection, 'Collections detail');
    }
}
