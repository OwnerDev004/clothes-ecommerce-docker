<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;

class ProductRepository extends BaseRepository
{

    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $normalized = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
        ksort($normalized);
        $cacheKey = 'products:public:getAll:' . md5(json_encode($normalized));
        $ttlSeconds = 300;

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($filters) {
            $searchText = trim((string) ($filters['search_txt'] ?? ''));
            $category = $filters['category'] ?? null;
            $subCategory = $filters['sub_category'] ?? null;
            $price = $filters['price'] ?? null;
            $priceMin = $filters['price_min'] ?? null;
            $priceMax = $filters['price_max'] ?? null;
            $color = $filters['color'] ?? null;
            $size = $filters['size'] ?? null;
            $collection = $filters['collection'] ?? ($filters['dress_style'] ?? null);

            $query = $this->model->newQuery()->select('products.*')->with([
                'thumbnail:id,product_id,image_url,image_type,sort_order',
                'images' => function ($q) {
                    $q->select('id', 'product_id', 'image_url', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
                'brand:id,name,slug,image_url',
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
                            ->where('categories.slug', $category);
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

            $perPage = (int) ($filters['per_page'] ?? 12);
            if ($perPage < 1) {
                $perPage = 12;
            }
            if ($perPage > 50) {
                $perPage = 50;
            }

            return $query->orderByDesc('products.id')->paginate($perPage);
        });
    }

    public function findById(int $id): ?Product
    {
        return $this->model->newQuery()
            ->with([
                'category:id,name,slug',
                'subCategory:id,category_id,name,slug',
                'collections:id,name,slug',
                'brand:id,name,slug,image_url',
                'thumbnail:id,product_id,image_url,image_type,sort_order',
                'images' => function ($q) {
                    $q->select('id', 'product_id', 'image_url', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
                'variants' => function ($q) {
                    $q->select('id', 'product_id', 'color_id', 'size_id', 'stock_quantity', 'sell_price', 'cost_price')
                        ->with([
                            'color:id,name,hex_code',
                            'size:id,name,sort_order',
                        ]);
                },
            ])
            ->find($id);
    }
}
