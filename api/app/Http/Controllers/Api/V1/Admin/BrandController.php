<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Brand\ListBrandRequest;
use App\Http\Requests\Api\V1\Admin\Brand\StoreBrandRequest;
use App\Http\Requests\Api\V1\Admin\Brand\UpdateBrandRequest;
use App\Http\Resources\Api\V1\Admin\BrandResource;
use App\Models\Brand;
use App\Services\Api\V1\Admin\BrandService;
use App\Traits\ApiResponse;

class BrandController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BrandService $brandService)
    {
    }

    public function index(ListBrandRequest $request)
    {
        $brands = $this->brandService->paginate($request->validated());
        $brands->setCollection($brands->getCollection()->map(fn($brand) => BrandResource::make($brand)->resolve()));

        return $this->paginate($brands, 'Brands list');
    }

    public function show(Brand $brand)
    {
        return $this->success(new BrandResource($brand), 'Brand detail');
    }

    public function store(StoreBrandRequest $request)
    {
        $brand = $this->brandService->store($request->validated(), $request->file('image'));

        return $this->created(new BrandResource($brand), 'Brand created');
    }

    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $brand = $this->brandService->update($brand, $request->validated(), $request->file('image'));

        return $this->success(new BrandResource($brand->fresh()), 'Brand updated');
    }

    public function destroy(Brand $brand)
    {
        $this->brandService->destroy($brand);

        return $this->success(null, 'Brand deleted');
    }
}

