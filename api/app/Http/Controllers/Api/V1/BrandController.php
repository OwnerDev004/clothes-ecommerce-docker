<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class BrandController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/api/v1/brands',
        tags: ['Brands'],
        summary: 'Get brands',
        responses: [
            new OA\Response(response: 200, description: 'Brands list'),
        ]
    )]
    public function index()
    {
        $brands = Brand::query()
            ->select('id', 'name', 'slug', 'sort_order', 'image_url')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success($brands, 'Brands list');
    }
}
