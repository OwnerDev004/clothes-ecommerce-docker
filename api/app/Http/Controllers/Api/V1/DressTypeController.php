<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DressType;
use App\Traits\ApiResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DressTypeController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dressTypes = DressType::query()
            ->select('id', 'name', 'slug', 'sort_order', 'img', 'image_url')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success($dressTypes, 'Dress types list');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:dress_types,name'],
            'desc' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $dressTypeData = [
            'name' => $validated['name'],
            'desc' => $validated['desc'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        if ($request->hasFile('image')) {
            $upload = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'clothes_ecommerce/dress-types']
            );

            $dressTypeData['image_url'] = $upload['secure_url'] ?? null;
            $dressTypeData['image_public_id'] = $upload['public_id'] ?? null;
        }

        $dressType = DressType::create($dressTypeData);

        return $this->created($dressType, 'Dress type created');
    }

    /**
     * Display the specified resource.
     */
    public function show(DressType $dressType)
    {
        return $this->success($dressType, 'Dress type detail');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DressType $dressType)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('dress_types', 'name')->ignore($dressType->id)],
            'desc' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'sort_order' => ['sometimes', 'integer'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $updateData = [];
        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('desc', $validated)) {
            $updateData['desc'] = $validated['desc'];
        }
        if (array_key_exists('sort_order', $validated)) {
            $updateData['sort_order'] = (int) $validated['sort_order'];
        }

        if (($validated['remove_image'] ?? false) && $dressType->image_public_id) {
            Cloudinary::uploadApi()->destroy($dressType->image_public_id);
            $updateData['image_url'] = null;
            $updateData['image_public_id'] = null;
        }

        if ($request->hasFile('image')) {
            if ($dressType->image_public_id) {
                Cloudinary::uploadApi()->destroy($dressType->image_public_id);
            }

            $upload = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath(),
                ['folder' => 'clothes_ecommerce/dress-types']
            );

            $updateData['image_url'] = $upload['secure_url'] ?? null;
            $updateData['image_public_id'] = $upload['public_id'] ?? null;
        }

        $dressType->update($updateData);

        return $this->success($dressType, 'Dress type updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DressType $dressType)
    {
        if ($dressType->image_public_id) {
            Cloudinary::uploadApi()->destroy($dressType->image_public_id);
        }

        $dressType->delete();

        return $this->success(null, 'Dress type deleted');
    }
}
