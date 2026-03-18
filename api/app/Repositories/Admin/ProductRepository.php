<?php

namespace App\Repositories\Admin;

use App\Models\Product;
use App\Repositories\BaseRepository;
use App\Repositories\ProductRepository as PublicProductRepository;
use App\Services\Api\V1\Image\ImageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductRepository extends BaseRepository
{
    private const ADMIN_LIST_VERSION_KEY = 'products:admin:getAll:version';
    private const ADMIN_LIST_TTL_SECONDS = 300;
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
        $cacheVersion = self::getAdminListCacheVersion();
        $cacheKey = 'products:admin:getAll:v' . $cacheVersion . ':' . md5(json_encode($normalized));
        $ttlSeconds = self::ADMIN_LIST_TTL_SECONDS;

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($filters) {
            $searchText = trim((string) ($filters['search_txt'] ?? ''));
            $category = $filters['category'] ?? null;
            $subCategory = $filters['sub_category'] ?? null;
            $price = $filters['price'] ?? null;
            $priceMin = $filters['price_min'] ?? null;
            $priceMax = $filters['price_max'] ?? null;
            $color = $filters['color'] ?? null;
            $size = $filters['size'] ?? null;
            $brand = $filters['brand'] ?? null;
            $collection = $filters['collection'] ?? ($filters['dress_style'] ?? null);

            $query = $this->product_model->newQuery()->select('products.*')->with([
                'thumbnail:id,product_id,image_url,cloudinary_public_id,image_type,sort_order',
                'images' => function ($q) {
                    $q->select('id', 'product_id', 'image_url', 'cloudinary_public_id', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
                'subCategory:id,category_id,name,slug',
                'collections:id,name,slug',
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

            if (!is_null($collection) && $collection !== '') {
                if (is_numeric($collection)) {
                    $query->whereExists(function (Builder $sub) use ($collection) {
                        $sub->selectRaw('1')
                            ->from('collection_product')
                            ->whereColumn('collection_product.product_id', 'products.id')
                            ->where('collection_product.collection_id', (int) $collection);
                    });
                } else {
                    $query->whereExists(function (Builder $sub) use ($collection) {
                        $sub->selectRaw('1')
                            ->from('collection_product')
                            ->join('collections', 'collections.id', '=', 'collection_product.collection_id')
                            ->whereColumn('collection_product.product_id', 'products.id')
                            ->where(function ($w) use ($collection) {
                                $w->where('collections.slug', $collection)
                                    ->orWhere('collections.name', 'like', '%' . $collection . '%');
                            });
                    });
                }
            }

            if (!is_null($subCategory) && $subCategory !== '') {
                if (is_numeric($subCategory)) {
                    $query->where('products.sub_category_id', (int) $subCategory);
                } else {
                    $query->whereExists(function (Builder $sub) use ($subCategory) {
                        $sub->selectRaw('1')
                            ->from('sub_categories')
                            ->whereColumn('sub_categories.id', 'products.sub_category_id')
                            ->where('sub_categories.slug', $subCategory);
                    });
                }
            }

            if (!is_null($brand) && $brand !== '') {
                if (is_numeric($brand)) {
                    $query->where('products.brand_id', (int) $brand);
                } else {
                    $query->whereExists(function (Builder $sub) use ($brand) {
                        $sub->selectRaw('1')
                            ->from('brands')
                            ->whereColumn('brands.id', 'products.brand_id')
                            ->where(function ($w) use ($brand) {
                                $w->where('brands.slug', $brand)
                                    ->orWhere('brands.name', 'like', '%' . $brand . '%');
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

            if (!is_null($priceMin) && $priceMin !== '') {
                $query->where('products.price', '>=', (float) $priceMin);
            }

            if (!is_null($priceMax) && $priceMax !== '') {
                $query->where('products.price', '<=', (float) $priceMax);
            }

            return $query->orderByDesc('products.id')->get();
        });
    }

    //Store Products

    public function storeProduct(array $data = [])
    {
        $images = $data['images'] ?? [];
        $collectionIds = $data['collection_ids'] ?? null;
        unset($data['images'], $data['collection_ids']);

        return DB::transaction(function () use ($data, $images, $collectionIds) {
            $product = $this->product_model->create($data);
            if (is_array($collectionIds)) {
                $product->collections()->sync($collectionIds);
            }
            $this->image_service->syncProductImages($product, null, $images, false);

            DB::afterCommit(function () {
                self::bumpAdminListCacheVersion();
                PublicProductRepository::bumpPublicListCacheVersion();
            });

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
        $hasCollectionIdsPayload = array_key_exists('collection_ids', $data);

        $clearImages = filter_var($data['clear_images'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $existingImages = $hasExistingImagesPayload ? ($data['existing_images'] ?? []) : null;
        $newImages = $hasNewImagesPayload ? ($data['new_images'] ?? []) : null;
        $collectionIds = $hasCollectionIdsPayload ? ($data['collection_ids'] ?? []) : null;

        unset($data['clear_images']);
        unset($data['existing_images']);
        unset($data['new_images']);
        unset($data['collection_ids']);

        return DB::transaction(function () use ($id, $data, $clearImages, $existingImages, $newImages, $collectionIds, $hasCollectionIdsPayload, $hasClearImagesPayload, $hasExistingImagesPayload, $hasNewImagesPayload) {
            $product = $this->product_model->findOrFail($id);
            $product->update($data);

            if ($hasCollectionIdsPayload && is_array($collectionIds)) {
                $product->collections()->sync($collectionIds);
            }

            $shouldSyncImages = $hasClearImagesPayload || $hasExistingImagesPayload || $hasNewImagesPayload;

            if ($shouldSyncImages) {
                $this->image_service->syncProductImages($product, $existingImages, $newImages, $clearImages);
            }

            DB::afterCommit(function () {
                self::bumpAdminListCacheVersion();
                PublicProductRepository::bumpPublicListCacheVersion();
            });

            return $product->load([
                'thumbnail:id,product_id,image_url,cloudinary_public_id,image_type,sort_order',
                'images' => function ($query) {
                    $query->select('id', 'product_id', 'image_url', 'cloudinary_public_id', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
            ]);
        });

    }

    public function deleteProduct(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $product = $this->product_model
                ->with(['images:id,product_id,image_url,cloudinary_public_id'])
                ->findOrFail($id);

            $publicIdsToDelete = $product->images
                ->map(function ($image) {
                    return $image->cloudinary_public_id;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $product->delete();

            if (!empty($publicIdsToDelete)) {
                DB::afterCommit(function () use ($publicIdsToDelete) {
                    foreach ($publicIdsToDelete as $publicId) {
                        $this->image_service->deleteImage((string) $publicId);
                    }
                });
            }

            DB::afterCommit(function () {
                self::bumpAdminListCacheVersion();
                PublicProductRepository::bumpPublicListCacheVersion();
            });

            return true;
        });
    }

    public function findById($id)
    {
        $product = $this->product_model
            ->with(['category', 'subCategory', 'collections', 'images', 'variants'])
            ->find($id);
        return $product;
    }

    private static function bumpAdminListCacheVersion(): void
    {
        if (!Cache::has(self::ADMIN_LIST_VERSION_KEY)) {
            Cache::forever(self::ADMIN_LIST_VERSION_KEY, 1);
        }
        Cache::increment(self::ADMIN_LIST_VERSION_KEY);
    }

    private static function getAdminListCacheVersion(): int
    {
        $version = Cache::get(self::ADMIN_LIST_VERSION_KEY);
        if (is_null($version)) {
            Cache::forever(self::ADMIN_LIST_VERSION_KEY, 1);
            return 1;
        }

        return (int) $version;
    }

}
