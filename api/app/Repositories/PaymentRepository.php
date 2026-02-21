<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentRepository
{
    public function __construct(private readonly OrderLifecycleRepository $orderLifecycleRepository)
    {
    }

    public function createIntent(int $customerId, int $orderId, string $provider, string $currency = 'USD'): array
    {
        $provider = strtolower(trim($provider));
        $this->assertProviderSupported($provider);

        $order = Order::query()
            ->whereKey($orderId)
            ->where('customer_id', $customerId)
            ->first();

        if (!$order) {
            throw ValidationException::withMessages([
                'order_id' => ['Order not found'],
            ]);
        }

        if ($order->payment_state === 'paid') {
            throw ValidationException::withMessages([
                'order_id' => ['Order already paid'],
            ]);
        }

        if (in_array($order->status, ['cancelled', 'refunded', 'completed'], true)) {
            throw ValidationException::withMessages([
                'order_id' => ['Order is not payable'],
            ]);
        }

        $providerPaymentId = strtoupper($provider) . '-' . Str::upper(Str::random(20));
        $clientToken = hash('sha256', $providerPaymentId . '|token');
        $checkoutUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/')
            . '/payment/' . strtolower($provider) . '/checkout/' . $providerPaymentId;
        $expiresAt = now()->addMinutes((int) config('payment.intent_ttl_minutes', 30));

        $transaction = PaymentTransaction::updateOrCreate(
            ['order_id' => $order->id, 'provider' => $provider],
            [
                'provider_payment_id' => $providerPaymentId,
                'status' => 'pending',
                'amount' => (float) $order->total_price,
                'currency' => strtoupper($currency),
                'client_token' => $clientToken,
                'checkout_url' => $checkoutUrl,
                'expires_at' => $expiresAt,
            ]
        );

        $order->payment_provider = $provider;
        $order->payment_reference = $providerPaymentId;
        $order->payment_expires_at = $expiresAt;
        $order->save();

        return [
            'order_id' => $order->id,
            'provider' => $provider,
            'provider_payment_id' => $providerPaymentId,
            'status' => 'pending',
            'amount' => (float) $transaction->amount,
            'currency' => $transaction->currency,
            'client_token' => $clientToken,
            'checkout_url' => $checkoutUrl,
            'expires_at' => optional($expiresAt)->toISOString(),
        ];
    }

    public function processWebhook(string $provider, array $payload, string $signature, string $rawBody): array
    {
        $provider = strtolower(trim($provider));
        $this->assertProviderSupported($provider);
        $this->verifySignature($provider, $signature, $rawBody);

        $normalized = $this->normalizeWebhookPayload($provider, $payload);
        $eventId = (string) ($normalized['event_id'] ?? '');
        $eventType = (string) ($normalized['event_type'] ?? '');
        $data = (array) ($normalized['data'] ?? []);
        $orderId = (int) ($data['order_id'] ?? 0);
        $providerPaymentId = (string) ($data['provider_payment_id'] ?? '');

        if ($eventId === '' || $eventType === '' || $orderId <= 0) {
            throw ValidationException::withMessages([
                'payload' => ['Invalid webhook payload'],
            ]);
        }

        return DB::transaction(function () use ($provider, $payload, $eventId, $eventType, $orderId, $providerPaymentId) {
            $event = PaymentEvent::where('provider', $provider)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if ($event && $event->processed_at) {
                return ['duplicate' => true, 'event_id' => $eventId];
            }

            if (!$event) {
                $event = PaymentEvent::create([
                    'provider' => $provider,
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'order_id' => $orderId,
                    'payload' => $payload,
                ]);
                $event = PaymentEvent::whereKey($event->id)->lockForUpdate()->firstOrFail();
            }

            $order = Order::whereKey($orderId)->lockForUpdate()->first();
            if (!$order) {
                throw ValidationException::withMessages([
                    'order_id' => ['Order not found'],
                ]);
            }

            if (isset($data['amount']) && $data['amount'] !== null && (float) $data['amount'] !== (float) $order->total_price) {
                throw ValidationException::withMessages([
                    'amount' => ['Webhook amount mismatch'],
                ]);
            }

            $paymentTx = PaymentTransaction::updateOrCreate(
                ['order_id' => $order->id, 'provider' => $provider],
                [
                    'provider_payment_id' => $providerPaymentId !== '' ? $providerPaymentId : ($order->payment_reference ?: null),
                    'amount' => (float) $order->total_price,
                    'currency' => (string) ($data['currency'] ?? 'USD'),
                ]
            );

            $normalizedType = strtolower($eventType);
            if (in_array($normalizedType, ['payment.succeeded', 'payment.paid'], true)) {
                $this->markPaid($order, $paymentTx);
            } elseif (in_array($normalizedType, ['payment.failed', 'payment.expired', 'payment.canceled'], true)) {
                $this->markFailedOrExpired($order, $paymentTx, $normalizedType);
            } elseif (in_array($normalizedType, ['payment.refunded'], true)) {
                $this->markRefunded($order, $paymentTx);
            }

            $paymentTx->last_event_at = now();
            $paymentTx->save();

            $event->processed_at = now();
            $event->save();

            return [
                'duplicate' => false,
                'event_id' => $eventId,
                'order_id' => $order->id,
                'status' => $order->fresh()->status,
                'payment_state' => $order->fresh()->payment_state,
            ];
        });
    }

    private function assertProviderSupported(string $provider): void
    {
        $providers = (array) config('payment.providers', []);
        if (!array_key_exists($provider, $providers)) {
            throw ValidationException::withMessages([
                'provider' => ['Unsupported payment provider'],
            ]);
        }
    }

    private function normalizeWebhookPayload(string $provider, array $payload): array
    {
        if ($provider !== 'khrqr') {
            return [
                'event_id' => (string) ($payload['event_id'] ?? ''),
                'event_type' => (string) ($payload['event_type'] ?? ''),
                'data' => (array) ($payload['data'] ?? []),
            ];
        }

        $status = strtolower((string) ($payload['status'] ?? $payload['transaction_status'] ?? $payload['event_type'] ?? ''));
        $eventType = match ($status) {
            'success', 'successful', 'paid', 'succeeded', 'completed' => 'payment.succeeded',
            'failed', 'declined', 'error' => 'payment.failed',
            'expired', 'timeout', 'timed_out' => 'payment.expired',
            'canceled', 'cancelled' => 'payment.canceled',
            'refunded' => 'payment.refunded',
            default => '',
        };

        $orderId = (int) ($payload['order_id']
            ?? $payload['orderId']
            ?? $payload['merchant_order_id']
            ?? $payload['merchantOrderId']
            ?? 0);

        $providerPaymentId = (string) ($payload['provider_payment_id']
            ?? $payload['providerPaymentId']
            ?? $payload['payment_id']
            ?? $payload['paymentId']
            ?? $payload['transaction_id']
            ?? $payload['transactionId']
            ?? '');

        $eventId = (string) ($payload['event_id']
            ?? $payload['eventId']
            ?? $payload['id']
            ?? $providerPaymentId);

        return [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'data' => [
                'order_id' => $orderId,
                'provider_payment_id' => $providerPaymentId,
                'amount' => $payload['amount'] ?? null,
                'currency' => $payload['currency'] ?? 'USD',
            ],
        ];
    }

    private function markPaid(Order $order, PaymentTransaction $paymentTx): void
    {
        $paymentTx->status = 'paid';

        if ($order->payment_state === 'paid') {
            return;
        }

        $order->payment_state = 'paid';
        $order->payment_status = 'paid';
        $order->status = in_array($order->status, ['pending', 'paid'], true) ? 'processing' : $order->status;
        $order->order_status = in_array($order->order_status, ['pending'], true) ? 'processing' : $order->order_status;
        $order->paid_at = $order->paid_at ?? now();
        $order->save();
    }

    private function markFailedOrExpired(Order $order, PaymentTransaction $paymentTx, string $eventType): void
    {
        if (in_array($order->payment_state, ['refunded', 'cancelled', 'expired'], true)) {
            return;
        }

        $paymentTx->status = str_contains($eventType, 'expired') ? 'expired' : (str_contains($eventType, 'canceled') ? 'canceled' : 'failed');

        if ($order->payment_state !== 'paid') {
            $order->payment_state = $paymentTx->status;
            $order->payment_status = 'failed';
            $order->status = 'cancelled';
            $order->order_status = 'pending';
            $order->cancelled_at = $order->cancelled_at ?? now();
            $order->save();
            $this->orderLifecycleRepository->restoreStockIfNeeded($order);
        }
    }

    private function markRefunded(Order $order, PaymentTransaction $paymentTx): void
    {
        $paymentTx->status = 'refunded';
        $order->payment_state = 'refunded';
        $order->payment_status = 'failed';
        $order->status = 'refunded';
        $order->refunded_at = $order->refunded_at ?? now();
        $order->save();
        $this->orderLifecycleRepository->restoreStockIfNeeded($order);
    }

    private function verifySignature(string $provider, string $signature, string $rawBody): void
    {
        $secret = (string) config("payment.providers.{$provider}.webhook_secret");
        if ($secret === '') {
            throw ValidationException::withMessages([
                'provider' => ['Webhook secret is not configured'],
            ]);
        }

        if ($signature === '') {
            throw ValidationException::withMessages([
                'signature' => ['Missing signature'],
            ]);
        }

        $normalizedSignature = trim($signature);
        if (str_contains($normalizedSignature, '=')) {
            [, $normalizedSignature] = explode('=', $normalizedSignature, 2);
            $normalizedSignature = trim($normalizedSignature);
        }

        $computedHex = hash_hmac('sha256', $rawBody, $secret);
        $computedBase64 = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        if (!hash_equals($computedHex, $normalizedSignature) && !hash_equals($computedBase64, $normalizedSignature)) {
            throw ValidationException::withMessages([
                'signature' => ['Invalid signature'],
            ]);
        }
    }
}
