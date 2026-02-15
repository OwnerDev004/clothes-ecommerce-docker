<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\ProductFilterRequest;
use App\Http\Requests\Api\V1\Product\ProductStoreRequest;
use App\Http\Requests\Api\V1\Product\ProductUpdateRequest;
use App\Repositories\Admin\ProductRepository;
use App\Traits\ApiResponse;
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

        return $this->success("success get products", $products, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductStoreRequest $request)
    {
        $products = $this->productRepository->storeProduct($request->validated());

        return $this->success("success creadted product.", $products, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return $this->error("Product not exist", 404);
        }
        return $this->success("success filter Product", $product, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductUpdateRequest $request, int $id)
    {
        $payload = $request->validated();

        // Preserve explicit image intent from multipart/form-data even when files are empty.
        if ($request->exists('new_images') && !array_key_exists('new_images', $payload)) {
            $payload['new_images'] = [];
        }
        if ($request->exists('existing_images') && !array_key_exists('existing_images', $payload)) {
            $payload['existing_images'] = [];
        }
        if ($request->exists('clear_images') && !array_key_exists('clear_images', $payload)) {
            $payload['clear_images'] = $request->boolean('clear_images');
        }

        $product = $this->productRepository->updateProduct($id, $payload);

        return $this->success("success updated product.", $product, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->productRepository->deleteProduct($id); // throws 404 if not found
        return $this->success("Product deleted", null, 200);
    }
}
