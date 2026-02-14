<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\ProductFilterRequest;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Repositories\ProductRepository;
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
    public function index(ProductFilterRequest $request)
    {
        $filters = $request->validated();

        $products = $this->productRepository->getAll($filters);

        return $this->success("", $products, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
