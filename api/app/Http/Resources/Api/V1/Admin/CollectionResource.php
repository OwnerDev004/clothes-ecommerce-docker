<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'desc' => $this->desc,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'image_url' => $this->image_url,
            'products_count' => isset($this->products_count) ? (int) $this->products_count : null,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'brand_id' => $product->brand_id,
                        'category_id' => $product->category_id,
                        'sub_category_id' => $product->sub_category_id,
                        'price' => $product->price,
                    ];
                })->values();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

