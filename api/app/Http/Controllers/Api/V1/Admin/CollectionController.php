<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Traits\ApiResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CollectionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Collection::query()
            ->with(['category:id,name,slug'])
            ->withCount('products')
            ->select('id', 'category_id', 'name', 'slug', 'desc', 'sort_order', 'image_url', 'created_at', 'updated_at');

        if ($request->filled('search_txt')) {
            $search = trim((string) $request->query('search_txt'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('desc', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->query('category_id'));
        }

        $collections = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 20));

        return $this->paginate($collections, 'Collections list');
    }

    public function show(Collection $collection)
    {
        $collection->load([
            'category:id,name,slug',
            'products:id,name,slug,brand_id,category_id,sub_category_id,price',
        ]);

        return $this->success($collection, 'Collection detail');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:collections,name'],
            'desc' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'max:5120'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $collectionData = [
            'category_id' => (int) $validated['category_id'],
            'name' => $validated['name'],
            'desc' => $validated['desc'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        if ($request->hasFile('image')) {
            $upload = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'clothes_ecommerce/collections']
            );

            $collectionData['image_url'] = $upload['secure_url'] ?? null;
            $collectionData['image_public_id'] = $upload['public_id'] ?? null;
        }

        $collection = Collection::create($collectionData);
        if (!empty($validated['product_ids'])) {
            $collection->products()->sync($validated['product_ids']);
        }

        return $this->created($collection->load(['category:id,name,slug'])->loadCount('products'), 'Collection created');
    }

    public function update(Request $request, Collection $collection)
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('collections', 'name')->ignore($collection->id)],
            'desc' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'integer'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $updateData = [];
        if (array_key_exists('category_id', $validated)) {
            $updateData['category_id'] = (int) $validated['category_id'];
        }
        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('desc', $validated)) {
            $updateData['desc'] = $validated['desc'];
        }
        if (array_key_exists('sort_order', $validated)) {
            $updateData['sort_order'] = (int) $validated['sort_order'];
        }

        if (($validated['remove_image'] ?? false) && $collection->image_public_id) {
            Cloudinary::uploadApi()->destroy($collection->image_public_id);
            $updateData['image_url'] = null;
            $updateData['image_public_id'] = null;
        }

        if ($request->hasFile('image')) {
            if ($collection->image_public_id) {
                Cloudinary::uploadApi()->destroy($collection->image_public_id);
            }

            $upload = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'clothes_ecommerce/collections']
            );

            $updateData['image_url'] = $upload['secure_url'] ?? null;
            $updateData['image_public_id'] = $upload['public_id'] ?? null;
        }

        $collection->update($updateData);

        if (array_key_exists('product_ids', $validated)) {
            $collection->products()->sync($validated['product_ids'] ?? []);
        }

        return $this->success(
            $collection->fresh()->load(['category:id,name,slug'])->loadCount('products'),
            'Collection updated'
        );
    }

    public function destroy(Collection $collection)
    {
        if ($collection->image_public_id) {
            Cloudinary::uploadApi()->destroy($collection->image_public_id);
        }

        $collection->delete();

        return $this->success(null, 'Collection deleted');
    }
}
