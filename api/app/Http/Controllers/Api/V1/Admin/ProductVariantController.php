<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductVariant\ProductVariantFilterRequest;
use App\Http\Requests\Api\V1\ProductVariant\ProductVariantStoreRequest;
use App\Http\Requests\Api\V1\ProductVariant\ProductVariantUpdateRequest;
use App\Models\ProductVariant;
use App\Repositories\Admin\ProductVariantRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    use ApiResponse;
    protected $productVariantRepository;


    public function __construct(ProductVariantRepository $productVariantRepo)
    {
        $this->productVariantRepository = $productVariantRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(ProductVariantFilterRequest $request)
    {
        $filters = $request->validated();
        $variants = $this->productVariantRepository->getAll($filters);
        return $this->success($variants, 'Product Variant success get', 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductVariantStoreRequest $request)
    {
        $products = $this->productVariantRepository->storeProduct($request->validated());

        return $this->success("success creadted product.", $products, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $variant = $this->productVariantRepository->findById($id);
        if (!$variant) {
            return $this->error("Product Variant not exist", 404);
        }
        return $this->success("success filter Product Variant", $variant, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductVariantUpdateRequest $request, $id)
    {
        $payload = $request->validated();
        $productVariant = $this->productVariantRepository->updateVariant($id, $payload);
        return $this->success("Updated Product Variant Success", $productVariant, 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->productVariantRepository->deleteVariant($id); // throws 404 if not found
        return $this->success("Product Variant deleted", null, 200);
    }
}
