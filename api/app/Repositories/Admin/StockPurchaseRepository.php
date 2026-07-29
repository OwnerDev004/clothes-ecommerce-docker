<?php

namespace App\Repositories\Admin;

use App\Models\ProductVariant;
use App\Models\StockPurchase;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockPurchaseRepository
{
    private function relations(): array
    {
        return [
            'variant:id,product_id,size_id,color,stock_quantity,sell_price,cost_price',
            'variant.product:id,name,slug',
            'variant.size:id,name',
            'creator:id,first_name,last_name,user_name,email',
        ];
    }

    private function syncVariantCostPrice(int $variantId): void
    {
        $latestCost = StockPurchase::query()
            ->where('product_variant_id', $variantId)
            ->orderByDesc('id')
            ->value('cost_price');

        ProductVariant::query()
            ->whereKey($variantId)
            ->update([
                'cost_price' => $latestCost ?? 0,
            ]);
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = StockPurchase::query()
            ->with($this->relations());

        $search = trim((string) ($filters['search_txt'] ?? ''));
        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('stock_purchases.note', 'like', '%' . $search . '%')
                    ->orWhereHas('variant.product', function ($variantQuery) use ($search) {
                        $variantQuery->where('products.name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('variant.size', function ($sizeQuery) use ($search) {
                        $sizeQuery->where('sizes.name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('variant', function ($variantQuery) use ($search) {
                        $variantQuery->where('product_variants.color_name', 'like', '%' . $search . '%');
                    });
            });
        }

        if (!empty($filters['product_variant_id'])) {
            $query->where('product_variant_id', (int) $filters['product_variant_id']);
        }

        return $query
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function store(array $data, ?User $admin = null): StockPurchase
    {
        return DB::transaction(function () use ($data, $admin) {
            $variant = ProductVariant::whereKey((int) $data['product_variant_id'])
                ->lockForUpdate()
                ->first();

            if (!$variant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => ['Product variant not found'],
                ]);
            }

            $quantity = (int) $data['quantity'];
            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    'quantity' => ['Quantity must be at least 1'],
                ]);
            }

            $costPrice = round((float) $data['cost_price'], 2);
            $totalCost = round($quantity * $costPrice, 2);

            $purchase = StockPurchase::create([
                'product_variant_id' => $variant->id,
                'created_by' => $admin?->id,
                'quantity' => $quantity,
                'cost_price' => $costPrice,
                'total_cost' => $totalCost,
                'note' => $data['note'] ?? null,
            ]);

            $variant->increment('stock_quantity', $quantity);
            $variant->update([
                'cost_price' => $costPrice,
            ]);

            return $purchase->fresh($this->relations());
        });
    }

    public function update(int $id, array $data, ?User $admin = null): StockPurchase
    {
        return DB::transaction(function () use ($id, $data, $admin) {
            $purchase = StockPurchase::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$purchase) {
                throw ValidationException::withMessages([
                    'id' => ['Stock purchase not found'],
                ]);
            }

            $newVariantId = (int) $data['product_variant_id'];
            $newQuantity = (int) $data['quantity'];
            $newCostPrice = round((float) $data['cost_price'], 2);
            $newTotalCost = round($newQuantity * $newCostPrice, 2);

            $variantIds = array_values(array_unique([
                (int) $purchase->product_variant_id,
                $newVariantId,
            ]));

            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $currentVariant = $variants->get((int) $purchase->product_variant_id);
            $targetVariant = $variants->get($newVariantId);

            if (!$currentVariant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => ['Current product variant not found'],
                ]);
            }

            if (!$targetVariant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => ['Target product variant not found'],
                ]);
            }

            $currentQuantity = (int) $purchase->quantity;

            if ($currentVariant->id === $targetVariant->id) {
                $delta = $newQuantity - $currentQuantity;

                if ($delta > 0) {
                    $currentVariant->increment('stock_quantity', $delta);
                } elseif ($delta < 0) {
                    $decrease = abs($delta);
                    if ((int) $currentVariant->stock_quantity < $decrease) {
                        throw ValidationException::withMessages([
                            'quantity' => ['Not enough stock to reduce this purchase'],
                        ]);
                    }

                    $currentVariant->decrement('stock_quantity', $decrease);
                }
            } else {
                if ((int) $currentVariant->stock_quantity < $currentQuantity) {
                    throw ValidationException::withMessages([
                        'product_variant_id' => ['Not enough stock to move this purchase'],
                    ]);
                }

                $currentVariant->decrement('stock_quantity', $currentQuantity);
                $targetVariant->increment('stock_quantity', $newQuantity);
            }

            $purchase->update([
                'product_variant_id' => $targetVariant->id,
                'quantity' => $newQuantity,
                'cost_price' => $newCostPrice,
                'total_cost' => $newTotalCost,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncVariantCostPrice((int) $currentVariant->id);
            if ($targetVariant->id !== $currentVariant->id) {
                $this->syncVariantCostPrice((int) $targetVariant->id);
            }

            return $purchase->fresh($this->relations());
        });
    }

    public function destroy(int $id): void
    {
        DB::transaction(function () use ($id) {
            $purchase = StockPurchase::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$purchase) {
                throw ValidationException::withMessages([
                    'id' => ['Stock purchase not found'],
                ]);
            }

            $variant = ProductVariant::query()
                ->whereKey((int) $purchase->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (!$variant) {
                throw ValidationException::withMessages([
                    'product_variant_id' => ['Product variant not found'],
                ]);
            }

            $quantity = (int) $purchase->quantity;
            if ((int) $variant->stock_quantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Not enough stock to delete this purchase'],
                ]);
            }

            $variant->decrement('stock_quantity', $quantity);
            $purchase->delete();
            $this->syncVariantCostPrice((int) $variant->id);
        });
    }
}
