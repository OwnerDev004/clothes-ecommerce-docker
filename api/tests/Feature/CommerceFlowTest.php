<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cart_add_update_remove_flow(): void
    {
        [$customer, $headers] = $this->createCustomerWithHeaders('cart');
        $variant = $this->createVariant(10);

        $this->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ], $headers)->assertStatus(200)
            ->assertJsonPath('data.total_quantity', 2);

        $this->putJson("/api/v1/cart/items/{$variant->id}", [
            'quantity' => 5,
        ], $headers)->assertStatus(200)
            ->assertJsonPath('data.total_quantity', 5);

        $this->deleteJson("/api/v1/cart/items/{$variant->id}", [], $headers)
            ->assertStatus(200)
            ->assertJsonPath('data.item_count', 0);
    }

    public function test_checkout_applies_valid_voucher_and_records_usage(): void
    {
        [$customer, $headers] = $this->createCustomerWithHeaders('voucher');
        $variant = $this->createVariant(10, 50);
        $voucher = Voucher::create([
            'code' => 'SAVE10',
            'name' => 'Save 10%',
            'is_active' => true,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order_amount' => 10,
            'max_order' => 100,
            'max_uses_per_customer' => 2,
            'expires_at' => now()->addDay()->toDateString(),
        ]);

        $this->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ], $headers)->assertStatus(200);

        $response = $this->postJson('/api/v1/checkout', [
            'shipping_province' => 'Phnom Penh',
            'payment_method' => 'khqr',
            'voucher_code' => $voucher->code,
        ], $headers)->assertStatus(201);

        $response->assertJsonPath('data.summary.subtotal', 100);
        $response->assertJsonPath('data.summary.discount', 10);

        $orderId = $response->json('data.order.id');
        $this->assertDatabaseHas('voucher_uses', [
            'order_id' => $orderId,
            'customer_id' => $customer->id,
            'voucher_id' => $voucher->id,
        ]);
    }

    public function test_checkout_revalidates_stock_and_prevents_oversell(): void
    {
        [$customerOne, $headersOne] = $this->createCustomerWithHeaders('c1');
        [$customerTwo, $headersTwo] = $this->createCustomerWithHeaders('c2');
        $variant = $this->createVariant(1, 20);

        $this->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], $headersOne)->assertStatus(200);

        $this->postJson('/api/v1/checkout', [
            'shipping_province' => 'Phnom Penh',
            'payment_method' => 'khqr',
        ], $headersOne)->assertStatus(201);

        $existingCartId = DB::table('carts')->where('customer_id', $customerTwo->id)->value('id');
        if ($existingCartId) {
            $cartId = (int) $existingCartId;
        } else {
            $cartId = DB::table('carts')->insertGetId([
                'customer_id' => $customerTwo->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('cart_items')->updateOrInsert([
            'cart_id' => $cartId,
            'product_variant_id' => $variant->id,
        ], [
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/checkout', [
            'shipping_province' => 'Phnom Penh',
            'payment_method' => 'khqr',
        ], $headersTwo)->assertStatus(422);
    }

    public function test_payment_webhook_paid_marks_order_paid_and_processing(): void
    {
        config(['payment.providers.mockpay.webhook_secret' => 'test-secret']);

        [$customer, $headers] = $this->createCustomerWithHeaders('paid');
        $variant = $this->createVariant(5, 30);

        $this->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], $headers)->assertStatus(200);

        $checkout = $this->postJson('/api/v1/checkout', [
            'shipping_province' => 'Phnom Penh',
            'payment_method' => 'khqr',
        ], $headers)->assertStatus(201);

        $orderId = $checkout->json('data.order.id');

        $intent = $this->postJson('/api/v1/payments/intent', [
            'order_id' => $orderId,
            'provider' => 'mockpay',
        ], $headers)->assertStatus(201);

        $providerPaymentId = $intent->json('data.provider_payment_id');
        $payload = [
            'event_id' => 'evt_paid_1',
            'event_type' => 'payment.succeeded',
            'data' => [
                'order_id' => $orderId,
                'provider_payment_id' => $providerPaymentId,
                'amount' => 31.50,
                'currency' => 'USD',
            ],
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'test-secret');

        $this->postJson('/api/v1/payments/webhook/mockpay', $payload, [
            'X-Payment-Signature' => $signature,
        ])->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_state' => 'paid',
            'status' => 'processing',
        ]);
    }

    public function test_failed_payment_restores_stock(): void
    {
        config(['payment.providers.mockpay.webhook_secret' => 'test-secret']);

        [$customer, $headers] = $this->createCustomerWithHeaders('fail');
        $variant = $this->createVariant(2, 40);

        $this->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ], $headers)->assertStatus(200);

        $checkout = $this->postJson('/api/v1/checkout', [
            'shipping_province' => 'Phnom Penh',
            'payment_method' => 'khqr',
        ], $headers)->assertStatus(201);

        $orderId = $checkout->json('data.order.id');
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 0]);

        $intent = $this->postJson('/api/v1/payments/intent', [
            'order_id' => $orderId,
            'provider' => 'mockpay',
        ], $headers)->assertStatus(201);

        $payload = [
            'event_id' => 'evt_failed_1',
            'event_type' => 'payment.failed',
            'data' => [
                'order_id' => $orderId,
                'provider_payment_id' => $intent->json('data.provider_payment_id'),
                'amount' => 81.50,
                'currency' => 'USD',
            ],
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'test-secret');

        $this->postJson('/api/v1/payments/webhook/mockpay', $payload, [
            'X-Payment-Signature' => $signature,
        ])->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'stock_quantity' => 2]);
    }

    public function test_khrqr_webhook_is_disabled_by_default(): void
    {
        config([
            'payment.providers.khrqr.webhook_secret' => 'khqr-secret',
            'payment.providers.khrqr.webhook_enabled' => false,
        ]);

        [$customer, $headers] = $this->createCustomerWithHeaders('khqr_webhook');
        $variant = $this->createVariant(1, 20);

        $this->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], $headers)->assertStatus(200);

        $checkout = $this->postJson('/api/v1/checkout', [
            'shipping_province' => 'Phnom Penh',
            'payment_method' => 'khqr',
        ], $headers)->assertStatus(201);

        $orderId = $checkout->json('data.order.id');

        $intent = $this->postJson('/api/v1/payments/intent', [
            'order_id' => $orderId,
            'provider' => 'khrqr',
        ], $headers)->assertStatus(201);

        $providerPaymentId = $intent->json('data.provider_payment_id');
        $payload = [
            'transaction_id' => $providerPaymentId,
            'status' => 'SUCCESS',
            'order_id' => $orderId,
            'amount' => 20.00,
            'currency' => 'USD',
        ];
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $raw, 'khqr-secret');

        $this->postJson('/api/v1/payments/webhook/khrqr', $payload, [
            'X-KHQR-Signature' => 'sha256=' . $signature,
        ])->assertStatus(422)
            ->assertJsonPath('errors.provider.0', 'Khrqr webhook is disabled');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_state' => 'pending',
        ]);
    }

    public function test_khrqr_polling_updates_order_state(): void
    {
        config([
            'payment.providers.khrqr.webhook_secret' => 'khqr-secret',
            'payment.providers.khrqr.check_payment_endpoint' => 'https://khqr.example/check_payment',
            'payment.providers.khrqr.api_key' => 'test-key',
            'payment.providers.khrqr.poll_timeout' => 5,
        ]);

        [$customer, $headers] = $this->createCustomerWithHeaders('khqr_poll');
        $variant = $this->createVariant(3, 25);

        $this->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], $headers)->assertStatus(200);

        $checkout = $this->postJson('/api/v1/checkout', [
            'shipping_province' => 'Phnom Penh',
            'payment_method' => 'khqr',
        ], $headers)->assertStatus(201);

        $orderId = $checkout->json('data.order.id');

        $intent = $this->postJson('/api/v1/payments/intent', [
            'order_id' => $orderId,
            'provider' => 'khrqr',
        ], $headers)->assertStatus(201);

        $providerPaymentId = $intent->json('data.provider_payment_id');

        Http::fake([
            'https://khqr.example/check_payment' => Http::response([
                'transaction_id' => $providerPaymentId,
                'status' => 'SUCCESS',
                'order_id' => $orderId,
                'amount' => 26.50,
                'currency' => 'USD',
            ], 200),
        ]);

        $transaction = PaymentTransaction::where('order_id', $orderId)
            ->where('provider', 'khrqr')
            ->first();

        $this->assertNotNull($transaction?->poll_hash);
        $this->assertNotNull($intent->json('data.poll_hash'));
        $this->assertEquals($transaction->poll_hash, $intent->json('data.poll_hash'));

        $this->getJson("/api/v1/payments/khrqr/check/{$transaction->poll_hash}", $headers)
            ->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'payment_state' => 'paid',
            'status' => 'processing',
        ]);
    }

    public function test_customer_and_admin_order_management_transitions_are_guarded(): void
    {
        [$customer, $customerHeaders] = $this->createCustomerWithHeaders('order');
        [$admin, $adminHeaders] = $this->createAdminWithHeaders('order_admin');
        $variant = $this->createVariant(4, 25);

        $this->postJson('/api/v1/cart/items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], $customerHeaders)->assertStatus(200);

        $checkout = $this->postJson('/api/v1/checkout', [
            'shipping_province' => 'Phnom Penh',
            'payment_method' => 'cash_on_delivery',
        ], $customerHeaders)->assertStatus(201);
        $orderId = $checkout->json('data.order.id');

        $this->getJson('/api/v1/orders', $customerHeaders)->assertStatus(200);
        $this->getJson("/api/v1/orders/{$orderId}", $customerHeaders)->assertStatus(200);

        $this->patchJson("/api/v1/admin/orders/{$orderId}/status", [
            'status' => 'completed',
        ], $adminHeaders)->assertStatus(422);

        $this->patchJson("/api/v1/admin/orders/{$orderId}/status", [
            'status' => 'processing',
        ], $adminHeaders)->assertStatus(200);
    }

    private function createCustomerWithHeaders(string $suffix): array
    {
        $customer = Customer::create([
            'full_name' => 'Customer ' . $suffix,
            'user_name' => 'customer_' . $suffix,
            'email' => "customer_{$suffix}@example.com",
            'phone' => '85510000' . random_int(100, 999),
            'password' => Hash::make('password123'),
        ]);

        $token = auth('customer')->login($customer);

        return [$customer, ['Authorization' => 'Bearer ' . $token]];
    }

    private function createAdminWithHeaders(string $suffix): array
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'gender' => 'male',
            'user_name' => 'admin_' . $suffix,
            'phone' => '85520000' . random_int(100, 999),
            'email' => "admin_{$suffix}@example.com",
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $token = auth('admin')->login($admin);

        return [$admin, ['Authorization' => 'Bearer ' . $token]];
    }

    private function createVariant(int $stock = 10, float $sellPrice = 20): ProductVariant
    {
        $suffix = (string) random_int(1000, 9999);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Category ' . $suffix,
            'slug' => 'category-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $dressTypeId = DB::table('dress_types')->insertGetId([
            'name' => 'DressType ' . $suffix,
            'slug' => 'dress-type-' . $suffix,
            'sort_order' => 0,
            'img' => 'default_empty',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $colorId = DB::table('colors')->insertGetId([
            'name' => 'Color ' . $suffix,
            'hex_code' => '#' . substr(md5($suffix), 0, 6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $sizeId = DB::table('sizes')->insertGetId([
            'name' => 'Size' . $suffix,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::create([
            'sku' => 'SKU-' . $suffix,
            'slug' => 'product-' . $suffix,
            'name' => 'Product ' . $suffix,
            'price' => $sellPrice,
            'category_id' => $categoryId,
            'dress_type_id' => $dressTypeId,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'color_id' => $colorId,
            'size_id' => $sizeId,
            'stock_quantity' => $stock,
            'sell_price' => $sellPrice,
            'cost_price' => round($sellPrice * 0.6, 2),
        ]);
    }
}
