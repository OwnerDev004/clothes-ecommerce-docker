<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductVariant\ProductVariantFilterRequest;
use App\Http\Requests\Api\V1\ProductVariant\ProductVariantStoreRequest;
use App\Models\ProductVariant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    use ApiResponse;
    protected $productVariantRepository;
    public function __construct()
    {
        //    $this->productVariantRepository = 
    }
    /**
     * Display a listing of the resource.
     */
    public function index(ProductVariantFilterRequest $request)
    {
        $filters = $request->validated();
        // $variants = $this
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductVariantStoreRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductVariant $productVariant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductVariant $productVariant)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductVariant $productVariant)
    {
        //
    }
}
