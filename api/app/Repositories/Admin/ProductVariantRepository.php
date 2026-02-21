<?php

namespace App\Repositories\Admin;

use App\Models\ProductVariant;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductVariantRepository extends BaseRepository
{
    protected ProductVariant $product_variant_model;

    public function __construct(ProductVariant $product_variant_model)
    {
        parent::__construct($product_variant_model);
        $this->product_variant_model = $product_variant_model;
    }

    public function getAll(array $filters = []): Collection
    {
        $normalized = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
        ksort($normalized);
        $cacheKey = 'product_variants:getAll:' . md5(json_encode($normalized));
        $ttlSeconds = 300;

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($filters) {
            $productId = $filters['product_id'] ?? null;
            $price = $filters['price'] ?? null;
            $color = $filters['color'] ?? null;
            $size = $filters['size'] ?? null;
            $query = $this->product_variant_model->newQuery()
                ->select('product_variants.*')
                ->with([
                    'product:id,name,sku,slug',
                    'color:id,name,hex_code',
                    'size:id,name,sort_order',
                ]);

            if (!is_null($productId) && $productId !== '') {
                $query->where('product_variants.product_id', (int) $productId);
            }

            if (!is_null($color) && $color !== '') {
                if (is_numeric($color)) {
                    $query->where('product_variants.color_id', (int) $color);
                } else {
                    $query->whereExists(function (Builder $sub) use ($color) {
                        $sub->selectRaw('1')
                            ->from('colors')
                            ->whereColumn('colors.id', 'product_variants.color_id')
                            ->where(function (Builder $w) use ($color) {
                                $w->where('colors.name', 'like', '%' . $color . '%')
                                    ->orWhere('colors.hex_code', $color);
                            });
                    });
                }
            }

            if (!is_null($size) && $size !== '') {
                if (is_numeric($size)) {
                    $query->where('product_variants.size_id', (int) $size);
                } else {
                    $query->whereExists(function (Builder $sub) use ($size) {
                        $sub->selectRaw('1')
                            ->from('sizes')
                            ->whereColumn('sizes.id', 'product_variants.size_id')
                            ->where('sizes.name', 'like', '%' . $size . '%');
                    });
                }
            }

            if (!is_null($price) && $price !== '') {
                $query->where('product_variants.sell_price', '<=', (float) $price);
            }

            return $query->orderByDesc('product_variants.id')->get();
        });
    }

    public function storeVariant(array $data = []): ProductVariant
    {
        return DB::transaction(function () use ($data) {
            $variant = $this->product_variant_model->create($data);
            return $variant->load([
                'product:id,name,sku,slug',
                'color:id,name,hex_code',
                'size:id,name,sort_order',
            ]);
        });
    }

    public function updateVariant(int $id, array $data = []): ProductVariant
    {
        return DB::transaction(function () use ($id, $data) {
            $variant = $this->product_variant_model->findOrFail($id);
            $variant->update($data);
            return $variant->load([
                'product:id,name,sku,slug',
                'color:id,name,hex_code',
                'size:id,name,sort_order',
            ]);
        });
    }

    public function deleteVariant(int $id): bool
    {
        return (bool) $this->product_variant_model->whereKey($id)->delete();
    }

    public function findById($id)
    {
        return $this->product_variant_model
            ->with([
                'product:id,name,sku,slug',
                'color:id,name,hex_code',
                'size:id,name,sort_order',
            ])
            ->find($id);
    }

    // Backward-compatible aliases with old names.
    public function storeProduct(array $data = []): ProductVariant
    {
        return $this->storeVariant($data);
    }

    public function updateProduct(int $id, array $data = []): ProductVariant
    {
        return $this->updateVariant($id, $data);
    }

    public function deleteProduct(int $id): bool
    {
        return $this->deleteVariant($id);
    }
}
