<?php

namespace App\Repositories;

use App\Jobs\SendingInvoiceTelegramJob;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use KHQR\BakongKHQR;
use KHQR\Config\Constants;
use KHQR\Exceptions\KHQRException;
use KHQR\Helpers\KHQRData;
use KHQR\Models\MerchantInfo;

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

        $isRetryableFailedPayment = $order->status === 'cancelled'
            && in_array((string) $order->payment_state, ['failed', 'expired', 'canceled', 'cancelled'], true);

        if (in_array($order->status, ['cancelled', 'refunded', 'completed'], true) && !$isRetryableFailedPayment) {
            throw ValidationException::withMessages([
                'order_id' => ['Order is not payable'],
            ]);
        }
        $qrString = null;
        $khqrMd5 = null;
        $currencyIso = strtoupper($currency);

        if ($provider === 'khrqr') {
            [$qrString, $khqrMd5] = $this->generateKhrqrQrData($order, $currencyIso);
        }

        $providerPaymentId = strtoupper($provider) . '-' . Str::upper(Str::random(20));
        $clientToken = hash('sha256', $providerPaymentId . '|token');
        $checkoutUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/')
            . '/payment/' . strtolower($provider) . '/checkout/' . $providerPaymentId;
        $expiresAt = now()->addMinutes((int) config('payment.intent_ttl_minutes', 30));

        $pollHash = $this->buildPollHashForProvider($provider, $order->id, $providerPaymentId);
        $transaction = DB::transaction(function () use ($order, $provider, $providerPaymentId, $currency, $clientToken, $checkoutUrl, $expiresAt, $pollHash, $qrString, $khqrMd5) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $tx = PaymentTransaction::updateOrCreate(
                ['order_id' => $lockedOrder->id, 'provider' => $provider],
                [
                    'provider_payment_id' => $providerPaymentId,
                    'status' => 'pending',
                    'amount' => (float) $lockedOrder->total_price,
                    'currency' => strtoupper($currency),
                    'client_token' => $clientToken,
                    'checkout_url' => $checkoutUrl,
                    'expires_at' => $expiresAt,
                    'poll_hash' => $pollHash,
                    'qr_string' => $qrString,
                    'khqr_md5' => $khqrMd5,
                ]
            );

            $lockedOrder->payment_provider = $provider;
            $lockedOrder->payment_reference = $providerPaymentId;
            $lockedOrder->payment_expires_at = $expiresAt;
            $lockedOrder->save();

            return $tx;
        });

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
            'poll_hash' => $pollHash,
            'qr_string' => $qrString,
            'khqr_md5' => $khqrMd5,
        ];
    }

    public function processWebhook(string $provider, array $payload, string $signature, string $rawBody): array
    {
        $provider = strtolower(trim($provider));
        $this->assertProviderSupported($provider);

        $webhookEnabled = (bool) (config("payment.providers.{$provider}.webhook_enabled") ?? true);
        if (!$webhookEnabled) {
            throw ValidationException::withMessages([
                'provider' => [ucfirst($provider) . ' webhook is disabled'],
            ]);
        }

        $this->verifySignature($provider, $signature, $rawBody);

        $normalized = $this->normalizeWebhookPayload($provider, $payload);

        return $this->processPaymentEvent(
            $provider,
            $payload,
            (string) ($normalized['event_id'] ?? ''),
            (string) ($normalized['event_type'] ?? ''),
            (array) ($normalized['data'] ?? [])
        );
    }

    public function pollKhrqrPaymentStatus(int $customerId, string $pollHash): array
    {
        $transaction = PaymentTransaction::where('poll_hash', $pollHash)
            ->where('provider', 'khrqr')
            ->with('order')
            ->first();

        if (!$transaction || !$transaction->order) {
            throw ValidationException::withMessages([
                'hash' => ['KHQR payment not found'],
            ]);
        }

        if ($transaction->order->customer_id !== $customerId) {
            throw ValidationException::withMessages([
                'hash' => ['KHQR payment does not belong to the authenticated customer'],
            ]);
        }

        if ($transaction->khqr_md5 === null) {
            throw ValidationException::withMessages([
                'hash' => ['KHQR payment metadata is missing'],
            ]);
        }

        $channelTimeout = (int) (config('payment.providers.khrqr.poll_timeout') ?? 10);
        $payload = $this->postKhrqrPoll($transaction, $pollHash, $channelTimeout);

        $normalized = $this->normalizeWebhookPayload('khrqr', $payload);
        if (empty($normalized['event_type'])) {
            $transaction->last_event_at = now();
            $transaction->save();

            return [
                'duplicate' => false,
                'event_id' => (string) ($normalized['event_id'] ?? $transaction->provider_payment_id),
                'order_id' => $transaction->order_id,
                'status' => $transaction->order->status,
                'payment_state' => $transaction->order->payment_state,
            ];
        }

        return $this->processPaymentEvent(
            'khrqr',
            $payload,
            (string) ($normalized['event_id'] ?? ''),
            (string) ($normalized['event_type'] ?? ''),
            (array) ($normalized['data'] ?? [])
        );
    }

    private function processPaymentEvent(string $provider, array $payload, string $eventId, string $eventType, array $data): array
    {
        $orderId = (int) ($data['order_id'] ?? 0);
        if ($eventId === '' || $eventType === '' || $orderId <= 0) {
            throw ValidationException::withMessages([
                'payload' => ['Invalid webhook payload'],
            ]);
        }

        return DB::transaction(function () use ($provider, $payload, $eventId, $eventType, $orderId, $data) {
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

            $normalizedType = strtolower($eventType);
            $isFailedEvent = in_array($normalizedType, ['payment.failed', 'payment.expired', 'payment.canceled'], true);
            $providerPaymentId = (string) ($data['provider_payment_id'] ?? '');
            $paymentTx = PaymentTransaction::where('order_id', $order->id)
                ->where('provider', $provider)
                ->lockForUpdate()
                ->first();

            if (!$paymentTx && !$isFailedEvent) {
                $paymentTx = PaymentTransaction::create([
                    'order_id' => $order->id,
                    'provider' => $provider,
                    'provider_payment_id' => $providerPaymentId !== '' ? $providerPaymentId : ($order->payment_reference ?: null),
                    'status' => 'pending',
                    'amount' => (float) $order->total_price,
                    'currency' => (string) ($data['currency'] ?? 'USD'),
                    'poll_hash' => $this->buildPollHashForProvider($provider, $order->id, $providerPaymentId),
                ]);
            }

            if ($paymentTx && $providerPaymentId !== '' && $paymentTx->provider_payment_id !== $providerPaymentId) {
                $paymentTx->provider_payment_id = $providerPaymentId;
            }

            if (in_array($normalizedType, ['payment.succeeded', 'payment.paid'], true)) {
                $this->markPaid($order, $paymentTx);
            } elseif ($isFailedEvent) {
                $this->markFailedOrExpired($order, $paymentTx, $normalizedType);
            } elseif (in_array($normalizedType, ['payment.refunded'], true)) {
                $this->markRefunded($order, $paymentTx);
            }

            if ($paymentTx && $paymentTx->exists) {
                $paymentTx->last_event_at = now();
                $paymentTx->save();
            }

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

    private function buildPollHashForProvider(string $provider, int $orderId, string $providerPaymentId): ?string
    {
        if ($provider !== 'khrqr' || $providerPaymentId === '') {
            return null;
        }

        return md5($orderId . '|' . $providerPaymentId);
    }

    private function buildKhrqrHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];

        $apiKey = (string) (config('payment.providers.khrqr.api_key') ?? '');
        if ($apiKey !== '') {
            $headers['X-KHQR-API-KEY'] = $apiKey;
        }

        return $headers;
    }

    private function generateKhrqrQrData(Order $order, string $currencyIso): array
    {
        $merchantConfig = $this->resolveKhrqrMerchantConfig();
        $currencyCode = $this->toKhrqrCurrency($currencyIso);
        $amount = (float) $order->total_price;

        $merchantInfo = new MerchantInfo(
            bakongAccountID: $merchantConfig['account_id'],
            merchantName: $merchantConfig['merchant_name'],
            merchantCity: $merchantConfig['merchant_city'],
            merchantID: $merchantConfig['merchant_id'],
            acquiringBank: $merchantConfig['acquiring_bank'],
            accountInformation: $merchantConfig['account_information'] ?? null,
            currency: $currencyCode,
            amount: $amount,
            billNumber: (string) $order->id,
            storeLabel: $merchantConfig['store_label'] ?? null,
            terminalLabel: $merchantConfig['terminal_label'] ?? null,
            mobileNumber: $merchantConfig['mobile_number'] ?? null,
            purposeOfTransaction: $merchantConfig['purpose'] ?? null,
            languagePreference: $merchantConfig['language_preference'] ?? null,
            merchantNameAlternateLanguage: $merchantConfig['merchant_name_alt'] ?? null,
            merchantCityAlternateLanguage: $merchantConfig['merchant_city_alt'] ?? null,
            upiMerchantAccount: $merchantConfig['upi_merchant_account'] ?? null,
        );

        try {
            $response = BakongKHQR::generateMerchant($merchantInfo);
        } catch (KHQRException $exception) {
            throw ValidationException::withMessages([
                'khrqr' => [$exception->getMessage()],
            ]);
        }

        $data = is_array($response->data ?? null) ? $response->data : [];
        $qr = (string) ($data['qr'] ?? '');
        $md5 = (string) ($data['md5'] ?? '');

        if ($qr === '' || $md5 === '') {
            throw ValidationException::withMessages([
                'khrqr' => ['Failed to generate KHQR payload'],
            ]);
        }

        return [$qr, $md5];
    }

    /**
     * @return array<string, string|null>
     */
    private function resolveKhrqrMerchantConfig(): array
    {
        $config = (array) (config('payment.providers.khrqr.merchant') ?? []);
        $required = ['account_id', 'merchant_name', 'merchant_city', 'merchant_id', 'acquiring_bank'];
        $missing = array_filter($required, fn($key) => trim((string) ($config[$key] ?? '')) === '');

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'khrqr' => ['Missing KHQR merchant config: ' . implode(', ', $missing)],
            ]);
        }

        return $config;
    }

    private function toKhrqrCurrency(string $currencyIso): int
    {
        return match (strtoupper($currencyIso)) {
            'KHR' => KHQRData::CURRENCY_KHR,
            'USD' => KHQRData::CURRENCY_USD,
            default => throw ValidationException::withMessages([
                'currency' => ['Unsupported KHQR currency: ' . $currencyIso],
            ]),
        };
    }

    private function resolveKhrqrToken(): string
    {
        $config = (array) (config('payment.providers.khrqr') ?? []);
        $useSit = (bool) ($config['use_sit'] ?? false);

        return (string) ($useSit ? ($config['token_sit'] ?? '') : ($config['token'] ?? ''));
    }

    private function postKhrqrPoll(PaymentTransaction $transaction, string $pollHash, int $timeout): array
    {
        $payload = [
            'hash' => $pollHash,
            'transaction_id' => $transaction->provider_payment_id,
            'order_id' => $transaction->order_id,
            'md5' => $transaction->khqr_md5,
        ];

        $customEndpoint = trim((string) (config('payment.providers.khrqr.check_payment_endpoint') ?? ''));

        try {
            $isOfficialMd5Endpoint = str_contains(strtolower($customEndpoint), 'check_transaction_by_md5');
            if ($customEndpoint !== '') {
                $token = $this->resolveKhrqrToken();
                $headers = $this->buildKhrqrHeaders();
                if ($token !== '') {
                    $headers['Authorization'] = 'Bearer ' . $token;
                }

                $customPayload = $payload;
                if ($isOfficialMd5Endpoint) {
                    $customPayload = ['md5' => $transaction->khqr_md5];
                }

                $response = Http::withHeaders($headers)
                    ->timeout($timeout)
                    ->retry(2, 200, fn($exception) => $exception instanceof ConnectionException)
                    ->post($customEndpoint, $customPayload);
            } else {
                $token = $this->resolveKhrqrToken();
                if ($token === '') {
                    throw ValidationException::withMessages([
                        'provider' => ['KHQR API token is not configured'],
                    ]);
                }

                $endpoint = (bool) (config('payment.providers.khrqr.use_sit') ?? false)
                    ? Constants::SIT_CHECK_TRANSACTION_MD5_URL
                    : Constants::CHECK_TRANSACTION_MD5_URL;

                $response = Http::withToken($token)
                    ->acceptJson()
                    ->timeout($timeout)
                    ->retry(2, 200, fn($exception) => $exception instanceof ConnectionException)
                    ->post($endpoint, [
                        'md5' => $transaction->khqr_md5,
                    ]);

                if (!$response->successful()) {
                    throw ValidationException::withMessages([
                        'provider' => ['KHQR check endpoint returned an unexpected status'],
                    ]);
                }

                $body = $response->json();
                if (!is_array($body)) {
                    throw ValidationException::withMessages([
                        'provider' => ['Malformed KHQR check response'],
                    ]);
                }

                return $this->normalizeOfficialKhrqrResponse($body, $transaction);
            }

            if (!$response->successful()) {
                $responseBody = trim((string) $response->body());
                throw ValidationException::withMessages([
                    'provider' => ['KHQR check endpoint returned an unexpected status'],
                    'provider_response' => [$responseBody !== '' ? $responseBody : 'No response body'],
                ]);
            }

            $result = $response->json();
            if (!is_array($result)) {
                throw ValidationException::withMessages([
                    'provider' => ['Malformed KHQR check response'],
                ]);
            }

            if ($customEndpoint !== '' && $isOfficialMd5Endpoint) {
                return $this->normalizeOfficialKhrqrResponse($result, $transaction);
            }

            return $result + [
                'provider_payment_id' => $transaction->provider_payment_id,
                'order_id' => $transaction->order_id,
                'md5' => $transaction->khqr_md5,
            ];
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'provider' => ['Unable to reach KHQR check endpoint'],
            ]);
        }
    }

    private function normalizeOfficialKhrqrResponse(array $response, PaymentTransaction $transaction): array
    {
        $responseCode = isset($response['responseCode']) ? (int) $response['responseCode'] : null;
        $status = 'pending';

        if ($responseCode === 0) {
            $status = 'success';
        } elseif ($responseCode > 1) {
            $status = 'failed';
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        return [
            'event_id' => $transaction->provider_payment_id,
            'transaction_id' => $transaction->provider_payment_id,
            'provider_payment_id' => $transaction->provider_payment_id,
            'status' => $status,
            'order_id' => $transaction->order_id,
            'amount' => isset($data['amount']) ? (float) $data['amount'] : (float) $transaction->amount,
            'currency' => $data['currency'] ?? $transaction->currency,
            'md5' => $transaction->khqr_md5,
        ];
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

    private function markPaid(Order $order, ?PaymentTransaction $paymentTx): void
    {
        if ($paymentTx) {
            $paymentTx->status = 'paid';
        }

        if ($order->payment_state === 'paid') {
            return;
        }

        $order->payment_state = 'paid';
        $order->payment_status = 'paid';
        $order->status = in_array($order->status, ['pending', 'paid'], true) ? 'processing' : $order->status;
        $order->order_status = in_array($order->order_status, ['pending'], true) ? 'processing' : $order->order_status;
        $order->paid_at = $order->paid_at ?? now();
        $order->save();

        if ($order->payment_method === 'khqr') {
            $cart = $order->customer?->cart;
            if ($cart) {
                $cart->items()->delete();
            }
        }

        SendingInvoiceTelegramJob::dispatch($order->id)->afterCommit();
    }

    private function markFailedOrExpired(Order $order, ?PaymentTransaction $paymentTx, string $eventType): void
    {
        if (in_array($order->payment_state, ['refunded', 'cancelled', 'expired'], true)) {
            return;
        }

        $failedStatus = str_contains($eventType, 'expired') ? 'expired' : (str_contains($eventType, 'canceled') ? 'canceled' : 'failed');
        if ($paymentTx) {
            $paymentTx->status = $failedStatus;
        }

        if ($order->payment_state !== 'paid') {
            $order->payment_state = $failedStatus;
            $order->payment_status = 'failed';
            $order->status = 'cancelled';
            $order->order_status = 'pending';
            $order->cancelled_at = $order->cancelled_at ?? now();
            $order->save();
            $this->orderLifecycleRepository->restoreStockIfNeeded($order);
        }

        // Requirement: do not keep payment transaction rows for failed/expired/canceled payments.
        if ($paymentTx && $paymentTx->exists) {
            $paymentTx->delete();
        }

        // Remove KHQR orders after failure so customers can re-checkout from cart.
        if ($order->payment_method === 'khqr' && $order->payment_state !== 'paid') {
            $order->delete();
        }
    }

    private function markRefunded(Order $order, ?PaymentTransaction $paymentTx): void
    {
        if ($paymentTx) {
            $paymentTx->status = 'refunded';
        }
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

    public function cleanupFailedKhqrOrder(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            $order = Order::whereKey($orderId)->lockForUpdate()->first();
            if (!$order) {
                return;
            }

            if ($order->payment_method !== 'khqr') {
                return;
            }

            if ($order->payment_state === 'paid') {
                return;
            }

            $this->orderLifecycleRepository->restoreStockIfNeeded($order);
            $order->delete();
        });
    }

    public function cancelPendingKhqrOrder(int $orderId, int $customerId): void
    {
        DB::transaction(function () use ($orderId, $customerId) {
            $order = Order::whereKey($orderId)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw ValidationException::withMessages([
                    'order_id' => ['Order not found'],
                ]);
            }

            if ($order->payment_method !== 'khqr') {
                throw ValidationException::withMessages([
                    'order_id' => ['Order is not a KHQR payment'],
                ]);
            }

            if ($order->payment_state === 'paid') {
                throw ValidationException::withMessages([
                    'order_id' => ['Order already paid'],
                ]);
            }

            $this->orderLifecycleRepository->restoreStockIfNeeded($order);
            $order->delete();
        });
    }
}
