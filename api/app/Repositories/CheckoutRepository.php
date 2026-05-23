<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\VoucherUse;
use App\Repositories\AppSettingRepository;
use App\Services\Api\V1\OrderRealtimeAlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutRepository
{
    public function __construct(
        private readonly VoucherRepository $voucherRepository,
        private readonly OrderRealtimeAlertService $orderRealtimeAlertService,
        private readonly AppSettingRepository $appSettingRepository,
    ) {
    }

    public function checkout(int $customerId, array $payload): array
    {
        return DB::transaction(function () use ($customerId, $payload) {
            $cart = Cart::where('customer_id', $customerId)->first();
            if (!$cart) {
                throw ValidationException::withMessages([
                    'cart' => ['Cart is empty'],
                ]);
            }

            $cartItems = CartItem::where('cart_id', $cart->id)
                ->orderBy('product_variant_id')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Cart is empty'],
                ]);
            }

            $snapshots = [];
            $subtotal = 0.0;

            foreach ($cartItems as $cartItem) {
                $variant = ProductVariant::with([
                    'product:id,name,slug',
                    'size:id,name',
                ])->whereKey($cartItem->product_variant_id)
                    ->lockForUpdate()
                    ->first();

                if (!$variant) {
                    throw ValidationException::withMessages([
                        'variant_id' => ["Variant {$cartItem->product_variant_id} not found"],
                    ]);
                }

                if ($cartItem->quantity > $variant->stock_quantity) {
                    throw ValidationException::withMessages([
                        'stock' => ["Insufficient stock for variant {$variant->id}"],
                    ]);
                }

                $unitPrice = (float) ($variant->sell_price ?? 0);
                $unitCost = (float) ($variant->cost_price ?? 0);
                $lineTotal = round($unitPrice * $cartItem->quantity, 2);

                $subtotal += $lineTotal;

                $snapshots[] = [
                    'variant' => $variant,
                    'quantity' => (int) $cartItem->quantity,
                    'unit_price' => round($unitPrice, 2),
                    'unit_cost' => round($unitCost, 2),
                    'discount_amount' => 0.0,
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            [$voucher, $discount] = $this->resolveVoucherDiscount($customerId, $payload, $subtotal);

            $shippingFee = $this->calculateShippingFee((string) $payload['shipping_province'], $subtotal);
            $total = round($subtotal - $discount + $shippingFee, 2);

            if (array_key_exists('grand_total', $payload) && $payload['grand_total'] !== null && $payload['grand_total'] !== '') {
                $clientGrandTotal = round((float) $payload['grand_total'], 2);
                $total = max(0, $clientGrandTotal);
                $shippingFee = round(max(0, $total - $subtotal + $discount), 2);
            }

            $order = Order::create([
                'customer_id' => $customerId,
                'voucher_id' => $voucher?->id,
                'order_date' => now()->toDateString(),
                'subtotal_price' => $subtotal,
                'discount_amount' => $discount,
                'total_price' => $total,
                'shipping_province' => $payload['shipping_province'],
                'shipping_fee' => $shippingFee,
                'shipping_address' => $payload['shipping_address'] ?? null,
                'shipping_phone' => $payload['shipping_phone'] ?? null,
                'payment_method' => $payload['payment_method'] ?? 'cash_on_delivery',
                'payment_status' => 'pending',
                'status' => 'order_confirming',
            ]);

            foreach ($snapshots as $snapshot) {
                $variant = $snapshot['variant'];
                $quantity = $snapshot['quantity'];

                $updated = ProductVariant::whereKey($variant->id)
                    ->where('stock_quantity', '>=', $quantity)
                    ->decrement('stock_quantity', $quantity);

                if ($updated !== 1) {
                    throw ValidationException::withMessages([
                        'stock' => ["Insufficient stock for variant {$variant->id}"],
                    ]);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price' => $snapshot['unit_price'],
                    'unit_cost' => $snapshot['unit_cost'],
                    'discount_amount' => $snapshot['discount_amount'],
                    'total_price' => $snapshot['line_total'],
                ]);
            }

            if ($voucher && $discount > 0) {
                VoucherUse::create([
                    'voucher_id' => $voucher->id,
                    'order_id' => $order->id,
                    'customer_id' => $customerId,
                    'discount_amount' => $discount,
                    'used_at' => now()->toDateString(),
                ]);
            }

            // Keep cart items for KHQR until payment succeeds.
            if (($payload['payment_method'] ?? 'cash_on_delivery') !== 'khqr') {
                CartItem::where('cart_id', $cart->id)->delete();
            }

            $order->load([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product:id,name,slug',
                'items.variant.size:id,name',
                'voucher:id,code,name',
            ]);

            if (($payload['payment_method'] ?? 'cash_on_delivery') !== 'khqr') {
                $this->orderRealtimeAlertService->notifyAdminOrderCreated($order);
            }

            return [
                'order' => $order,
                'summary' => [
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'shipping_fee' => $shippingFee,
                    'total' => $total,
                ],
            ];
        });
    }

    private function calculateShippingFee(string $shippingProvince, float $subtotal): float
    {
        $province = strtolower(trim($shippingProvince));
        $settings = $this->appSettingRepository->current();

        $freeShippingThreshold = (float) ($settings->free_shipping_threshold ?? 0);
        if ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold) {
            return 0.0;
        }

        $shippingRates = collect($settings->shipping_rates ?? [])
            ->mapWithKeys(function ($row) {
                $key = strtolower(trim((string) ($row['province'] ?? '')));
                $fee = (float) ($row['fee'] ?? 0);

                return $key !== '' ? [$key => $fee] : [];
            })
            ->all();

        if (array_key_exists($province, $shippingRates)) {
            return (float) $shippingRates[$province];
        }

        $defaultShippingFee = (float) ($settings->shipping_fee ?? 0);
        if ($defaultShippingFee > 0) {
            return $defaultShippingFee;
        }

        $legacyRates = [
            'phnom penh' => 1.50,
            'kandal' => 2.00,
            'siem reap' => 2.50,
            'battambang' => 2.50,
            'preah sihanouk' => 3.00,
        ];

        return $legacyRates[$province] ?? 3.50;
    }

    private function resolveVoucherDiscount(int $customerId, array $payload, float $subtotal): array
    {
        $voucherCode = trim((string) ($payload['voucher_code'] ?? ''));
        if ($voucherCode !== '') {
            $voucherResult = $this->voucherRepository->validateAndCompute($customerId, $voucherCode, $subtotal);

            return [
                $voucherResult['voucher'],
                (float) $voucherResult['discount'],
            ];
        }

        $signupVoucher = $this->voucherRepository->getActiveSignupOffer();
        if (!$signupVoucher) {
            return [null, 0.0];
        }

        try {
            $voucherResult = $this->voucherRepository->validateAndCompute(
                $customerId,
                (string) $signupVoucher->code,
                $subtotal
            );

            return [
                $voucherResult['voucher'],
                (float) $voucherResult['discount'],
            ];
        } catch (ValidationException) {
            return [null, 0.0];
        }
    }
}
