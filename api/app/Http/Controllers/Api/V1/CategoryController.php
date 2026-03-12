<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Traits\ApiResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use ApiResponse;

    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $withProductCount = $request->boolean('with_product_count', false);

        $categories = $this->categoryRepository->getAll($withProductCount);

        return $this->success($categories, 'Categories list');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'des' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $categoryData = [
            'name' => $validated['name'],
            'des' => $validated['des'] ?? null,
        ];

        if ($request->hasFile('image')) {
            $upload = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'clothes_ecommerce/category-images']
            );

            $categoryData['image_url'] = $upload['secure_url'] ?? null;
            $categoryData['image_public_id'] = $upload['public_id'] ?? null;
        }

        $category = $this->categoryRepository->createCategory($categoryData);

        return $this->created($category, 'Category created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return $this->success(
            $category->only(['id', 'name', 'slug', 'des', 'image_url']),
            'Category detail'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'des' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $updateData = [];
        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('des', $validated)) {
            $updateData['des'] = $validated['des'];
        }

        if (($validated['remove_image'] ?? false) && $category->image_public_id) {
            Cloudinary::uploadApi()->destroy($category->image_public_id);
            $updateData['image_url'] = null;
            $updateData['image_public_id'] = null;
        }

        if ($request->hasFile('image')) {
            if ($category->image_public_id) {
                Cloudinary::uploadApi()->destroy($category->image_public_id);
            }

            $upload = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'clothes_ecommerce/category-images']
            );

            $updateData['image_url'] = $upload['secure_url'] ?? null;
            $updateData['image_public_id'] = $upload['public_id'] ?? null;
        }

        $category = $this->categoryRepository->updateCategory($category, $updateData);

        return $this->success($category, 'Category updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->image_public_id) {
            Cloudinary::uploadApi()->destroy($category->image_public_id);
        }

        $this->categoryRepository->deleteCategory($category);

        return $this->success(null, 'Category deleted');
    }
}
