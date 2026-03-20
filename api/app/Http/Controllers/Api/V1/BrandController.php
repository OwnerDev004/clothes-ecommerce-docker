<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ApiResponse;

class BrandController extends Controller
{
    use ApiResponse;

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
