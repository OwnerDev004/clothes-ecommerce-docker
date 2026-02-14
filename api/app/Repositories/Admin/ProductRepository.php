<?php

namespace App\Repositories\Admin;

use App\Models\Product;
use App\Repositories\BaseRepository;
use App\Services\Api\V1\Image\ImageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductRepository extends BaseRepository
{
    protected $product_model;

    // Services

    protected $image_service;
    public function __construct(Product $product_model, ImageService $image_service)
    {
        parent::__construct($product_model);
        $this->product_model = $product_model;
        $this->image_service = $image_service;
    }

    public function getAll(array $filters = []): Collection
    {
        $normalized = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
        ksort($normalized);
        $cacheKey = 'products:getAll:' . md5(json_encode($normalized));
        $ttlSeconds = 300;

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($filters) {
            $searchText = trim((string) ($filters['search_txt'] ?? ''));
            $category = $filters['category'] ?? null;
            $price = $filters['price'] ?? null;
            $color = $filters['color'] ?? null;
            $size = $filters['size'] ?? null;
            $dressStyle = $filters['dress_style'] ?? null;

            $query = $this->product_model->newQuery()->select('products.*')->with([
                'thumbnail:id,product_id,image_url,cloudinary_public_id,image_type,sort_order',
                'images' => function ($q) {
                    $q->select('id', 'product_id', 'image_url', 'cloudinary_public_id', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
            ]);

            if ($searchText !== '') {
                $query->where(function ($q) use ($searchText) {
                    $q->where('products.name', 'like', '%' . $searchText . '%')
                        ->orWhere('products.sku', 'like', '%' . $searchText . '%')
                        ->orWhere('products.slug', 'like', '%' . $searchText . '%')
                        ->orWhere('products.desc', 'like', '%' . $searchText . '%');
                });
            }

            if (!is_null($category) && $category !== '') {
                if (is_numeric($category)) {
                    $query->where('products.category_id', (int) $category);
                } else {
                    $query->whereExists(function (Builder $sub) use ($category) {
                        $sub->selectRaw('1')
                            ->from('categories')
                            ->whereColumn('categories.id', 'products.category_id')
                            ->where(function ($w) use ($category) {
                                $w->where('categories.slug', $category)
                                    ->orWhere('categories.name', 'like', '%' . $category . '%');
                            });
                    });
                }
            }

            if (!is_null($dressStyle) && $dressStyle !== '') {
                if (is_numeric($dressStyle)) {
                    $query->where('products.dress_type_id', (int) $dressStyle);
                } else {
                    $query->whereExists(function (Builder $sub) use ($dressStyle) {
                        $sub->selectRaw('1')
                            ->from('dress_types')
                            ->whereColumn('dress_types.id', 'products.dress_type_id')
                            ->where(function ($w) use ($dressStyle) {
                                $w->where('dress_types.slug', $dressStyle)
                                    ->orWhere('dress_types.name', 'like', '%' . $dressStyle . '%');
                            });
                    });
                }
            }

            if (!is_null($color) && $color !== '') {
                $query->whereExists(function (Builder $sub) use ($color) {
                    $sub->selectRaw('1')
                        ->from('product_variants')
                        ->join('colors', 'colors.id', '=', 'product_variants.color_id')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->where(function ($w) use ($color) {
                            if (is_numeric($color)) {
                                $w->where('colors.id', (int) $color);
                            } else {
                                $w->where('colors.name', 'like', '%' . $color . '%')
                                    ->orWhere('colors.hex_code', $color);
                            }
                        });
                });
            }

            if (!is_null($size) && $size !== '') {
                $query->whereExists(function (Builder $sub) use ($size) {
                    $sub->selectRaw('1')
                        ->from('product_variants')
                        ->join('sizes', 'sizes.id', '=', 'product_variants.size_id')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->where(function ($w) use ($size) {
                            if (is_numeric($size)) {
                                $w->where('sizes.id', (int) $size);
                            } else {
                                $w->where('sizes.name', 'like', '%' . $size . '%');
                            }
                        });
                });
            }

            if (!is_null($price) && $price !== '') {
                $query->where('products.price', '<=', (float) $price);
            }

            return $query->orderByDesc('products.id')->get();
        });
    }

    //Store Products

    public function storeProduct(array $data = [])
    {
        $images = $data['images'] ?? [];
        unset($data['images']);

        return DB::transaction(function () use ($data, $images) {
            $product = $this->product_model->create($data);
            $this->image_service->syncProductImages($product, null, $images, false);

            return $product->load([
                'thumbnail:id,product_id,image_url,cloudinary_public_id,image_type,sort_order',
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image_url', 'cloudinary_public_id', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
            ]);
        });
    }


    public function updateProduct(int $id, array $data = [])
    {
        $hasClearImagesPayload = array_key_exists('clear_images', $data);
        $hasExistingImagesPayload = array_key_exists('existing_images', $data);
        $hasNewImagesPayload = array_key_exists('new_images', $data);

        $clearImages = filter_var($data['clear_images'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $existingImages = $hasExistingImagesPayload ? ($data['existing_images'] ?? []) : null;
        $newImages = $hasNewImagesPayload ? ($data['new_images'] ?? []) : null;

        unset($data['clear_images']);
        unset($data['existing_images']);
        unset($data['new_images']);

        return DB::transaction(function () use ($id, $data, $clearImages, $existingImages, $newImages, $hasClearImagesPayload, $hasExistingImagesPayload, $hasNewImagesPayload) {
            $product = $this->product_model->findOrFail($id);
            $product->update($data);

            $shouldSyncImages = $hasClearImagesPayload || $hasExistingImagesPayload || $hasNewImagesPayload;

            if ($shouldSyncImages) {
                $this->image_service->syncProductImages($product, $existingImages, $newImages, $clearImages);
            }

            return $product->load([
                'thumbnail:id,product_id,image_url,cloudinary_public_id,image_type,sort_order',
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image_url', 'cloudinary_public_id', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
            ]);
        });

    }

    public function deleteProduct(array $data = [])
    {
    }

    public function findById($id)
    {

        $product = $this->product_model
            ->with(['category', 'dressType', 'images', 'variants'])
            ->findOrfail($id);
        return $product;
    }

}
