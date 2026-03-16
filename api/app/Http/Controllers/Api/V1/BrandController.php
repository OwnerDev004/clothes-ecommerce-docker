<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ApiResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'sort_order' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $brandData = [
            'name' => $validated['name'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        if ($request->hasFile('image')) {
            $upload = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'clothes_ecommerce/brand-images']
            );

            $brandData['image_url'] = $upload['secure_url'] ?? null;
            $brandData['image_public_id'] = $upload['public_id'] ?? null;
        }

        $brand = Brand::create($brandData);

        return $this->created($brand, 'Brand created');
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand->id)],
            'sort_order' => ['sometimes', 'integer'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $updateData = [];
        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('sort_order', $validated)) {
            $updateData['sort_order'] = (int) $validated['sort_order'];
        }

        if (($validated['remove_image'] ?? false) && $brand->image_public_id) {
            Cloudinary::uploadApi()->destroy($brand->image_public_id);
            $updateData['image_url'] = null;
            $updateData['image_public_id'] = null;
        }

        if ($request->hasFile('image')) {
            if ($brand->image_public_id) {
                Cloudinary::uploadApi()->destroy($brand->image_public_id);
            }

            $upload = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'clothes_ecommerce/brand-images']
            );

            $updateData['image_url'] = $upload['secure_url'] ?? null;
            $updateData['image_public_id'] = $upload['public_id'] ?? null;
        }

        $brand->update($updateData);

        return $this->success($brand, 'Brand updated');
    }

    public function destroy(Brand $brand)
    {
        if ($brand->image_public_id) {
            Cloudinary::uploadApi()->destroy($brand->image_public_id);
        }

        $brand->delete();

        return $this->success(null, 'Brand deleted');
    }
}
