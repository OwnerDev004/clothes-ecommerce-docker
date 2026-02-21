<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartRepository extends BaseRepository
{
    protected Cart $cartModel;

    public function __construct(Cart $cartModel)
    {
        parent::__construct($cartModel);
        $this->cartModel = $cartModel;
    }

    public function getCurrentCart(int $customerId): array
    {
        $cart = $this->getOrCreateCart($customerId);
        return $this->buildCartPayload($cart);
    }

    public function addItem(int $customerId, int $variantId, int $quantity): array
    {
        $cart = $this->getOrCreateCart($customerId);

        DB::transaction(function () use ($cart, $variantId, $quantity) {
            $variant = ProductVariant::whereKey($variantId)->lockForUpdate()->first();
            if (!$variant) {
                throw ValidationException::withMessages([
                    'variant_id' => ['Variant not found'],
                ]);
            }

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            $currentQty = $cartItem?->quantity ?? 0;
            $newQty = $currentQty + $quantity;

            if ($newQty > $variant->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Requested quantity exceeds stock'],
                ]);
            }

            if ($cartItem) {
                $cartItem->update(['quantity' => $newQty]);
                return;
            }

            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ]);
        });

        return $this->buildCartPayload($cart);
    }

    public function updateItem(int $customerId, int $variantId, int $quantity): ?array
    {
        $cart = $this->getOrCreateCart($customerId);
        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            throw new ModelNotFoundException('Variant not found');
        }

        if ($quantity > $variant->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Requested quantity exceeds stock'],
            ]);
        }

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->first();
        if (!$item) {
            return null;
        }

        $item->update(['quantity' => $quantity]);
        return $this->buildCartPayload($cart);
    }

    public function removeItem(int $customerId, int $variantId): ?array
    {
        $cart = $this->getOrCreateCart($customerId);
        $deleted = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->delete();

        if (!$deleted) {
            return null;
        }

        return $this->buildCartPayload($cart);
    }

    public function clear(int $customerId): array
    {
        $cart = $this->getOrCreateCart($customerId);
        CartItem::where('cart_id', $cart->id)->delete();

        return $this->buildCartPayload($cart);
    }

    private function getOrCreateCart(int $customerId): Cart
    {
        return $this->cartModel->firstOrCreate(['customer_id' => $customerId]);
    }

    private function buildCartPayload(Cart $cart): array
    {
        $cart->load([
            'items.variant.product:id,name,slug',
            'items.variant.color:id,name,hex_code',
            'items.variant.size:id,name',
        ]);

        $items = [];
        $subtotal = 0.0;
        $totalQuantity = 0;

        foreach ($cart->items as $item) {
            $variant = $item->variant;
            if (!$variant) {
                continue;
            }

            $unitPrice = (float) ($variant->sell_price ?? 0);
            $lineTotal = round($unitPrice * $item->quantity, 2);
            $subtotal += $lineTotal;
            $totalQuantity += (int) $item->quantity;

            $items[] = [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => optional($variant->product)->name,
                'product_slug' => optional($variant->product)->slug,
                'color' => optional($variant->color)->name,
                'size' => optional($variant->size)->name,
                'stock_quantity' => (int) $variant->stock_quantity,
                'quantity' => (int) $item->quantity,
                'unit_price' => round($unitPrice, 2),
                'line_total' => $lineTotal,
            ];
        }

        $subtotal = round($subtotal, 2);

        return [
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'item_count' => count($items),
            'total_quantity' => $totalQuantity,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'items' => $items,
        ];
    }
}
