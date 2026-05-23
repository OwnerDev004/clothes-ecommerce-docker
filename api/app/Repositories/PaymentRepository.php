<?php

namespace App\Repositories;

use App\Jobs\SendingInvoiceTelegramJob;
use App\Models\Order;
use App\Models\PaymentEvent;
use App\Models\PaymentTransaction;
use App\Services\Api\V1\Queue\TelegramSendingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use KHQR\BakongKHQR;
use KHQR\Exceptions\KHQRException;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;
use KHQR\Models\SourceInfo;
use function PHPUnit\Framework\isEmpty;

class PaymentRepository
{
    public function __construct(
        private readonly OrderLifecycleRepository $orderLifecycleRepository,
        private readonly AppSettingRepository $appSettingRepository,
        private readonly VoucherRepository $promo_voucher,
        private readonly \App\Services\Api\V1\OrderRealtimeAlertService $orderRealtimeAlertService,
    ) {
    }

    public function createIntent(
        int $customerId,
        int $orderId,
        string $provider,
        string $currency = 'USD',
        bool $is_complete_coupon = false,
        string $promo_code = '',
        float $fee_province = 0.0
    ): array {
        $merchantConfig = $this->resolveKhrqrMerchantConfig();
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

        if ($order->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'order_id' => ['Order already paid'],
            ]);
        }

        $isRetryableFailedPayment = $order->status === 'cancelled'
            && in_array((string) $order->payment_status, ['failed', 'expired', 'canceled', 'cancelled'], true);

        if (in_array($order->status, ['cancelled', 'refunded', 'completed'], true) && !$isRetryableFailedPayment) {
            throw ValidationException::withMessages([
                'order_id' => ['Order is not payable'],
            ]);
        }
        $currencyIso = strtoupper(trim($currency));

        [$paymentAmount, $baseCurrencyCode] = $this->resolvePaymentAmount(
            $promo_code,
            (float) $order->subtotal_price,
            $currencyIso,
            (float) $fee_province,
        );
        $currencyIso = $currencyIso !== '' ? $currencyIso : $baseCurrencyCode;
        $qrString = null;
        $khqrMd5 = null;
        $deepLink = null;

        if ($provider === 'khrqr') {

            [$qrString, $khqrMd5] = $this->generateKhrqrQrData($order, $currencyIso, $paymentAmount);
            $deepLink = $this->generateKhrqrDeepLink($qrString);
        }

        $providerPaymentId = strtoupper($provider) . '-' . Str::upper(Str::random(20));
        $clientToken = hash('sha256', $providerPaymentId . '|token');
        $checkoutUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/')
            . '/payment/' . strtolower($provider) . '/checkout/' . $providerPaymentId;
        $expiresAt = now()->addMinutes((int) config('payment.intent_ttl_minutes', 30));

        $pollHash = $this->buildPollHashForProvider($provider, $order->id, $providerPaymentId);
        $transaction = DB::transaction(function () use ($order, $provider, $providerPaymentId, $currencyIso, $paymentAmount, $clientToken, $checkoutUrl, $expiresAt, $pollHash, $qrString, $khqrMd5) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $tx = PaymentTransaction::updateOrCreate(
                ['order_id' => $lockedOrder->id, 'provider' => $provider],
                [
                    'provider_payment_id' => $providerPaymentId,
                    'status' => 'pending',
                    'amount' => $paymentAmount,
                    'currency' => $currencyIso,
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
            'merchant_name' => $merchantConfig['merchant_name'],
            'mechant_name' => $merchantConfig['merchant_name'],
            'provider_payment_id' => $providerPaymentId,
            'status' => 'pending',
            'amount' => (float) $transaction->amount,
            'amount_cents' => (int) round((float) $transaction->amount * 100),
            'currency' => $transaction->currency,
            'base_amount' => (float) $order->total_price,
            'base_currency' => $baseCurrencyCode,
            'client_token' => $clientToken,
            'checkout_url' => $checkoutUrl,
            'deep_link' => $deepLink,
            'expires_at' => optional($expiresAt)->toISOString(),
            'poll_hash' => $pollHash,
            'qr_string' => $qrString,
            'qr_string_base64' => $qrString !== null ? base64_encode($qrString) : null,
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

        $payload = $this->postKhrqrPoll($transaction, $pollHash);
        $normalized = $this->normalizeKhrqrCheckResponse((array) ($payload['response'] ?? []), $transaction);
        if (empty($normalized['event_type'])) {
            $transaction->last_event_at = now();
            $transaction->save();

            return [
                'duplicate' => false,
                'event_id' => (string) ($normalized['event_id'] ?? $transaction->provider_payment_id),
                'order_id' => $transaction->order_id,
                'status' => $transaction->order->status,
                'payment_status' => $transaction->order->payment_status,
            ];
        }

        return $this->processPaymentEvent(
            'khrqr',
            (array) ($payload['response'] ?? []),
            (string) ($normalized['event_id'] ?? ''),
            (string) ($normalized['event_type'] ?? ''),
            (array) ($normalized['data'] ?? [])
        );
    }

    public function simulatePaidForCustomer(int $customerId, int $orderId): array
    {
        $order = Order::query()
            ->whereKey($orderId)
            ->where('customer_id', $customerId)
            ->first();

        if (!$order) {
            throw ValidationException::withMessages([
                'order_id' => ['Order not found'],
            ]);
        }

        return DB::transaction(function () use ($order) {
            $paymentTx = PaymentTransaction::firstOrCreate(
                ['order_id' => $order->id, 'provider' => $order->payment_provider ?: 'manual'],
                [
                    'provider_payment_id' => $order->payment_reference ?: ('SIM-' . Str::upper(Str::random(8))),
                    'status' => 'pending',
                    'amount' => (float) $order->total_price,
                    'currency' => 'USD',
                ]
            );

            $this->markPaid($order, $paymentTx, $order->payment_provider ?: 'manual');

            $fresh = $order->fresh();

            return [
                'order_id' => $order->id,
                'payment_status' => $fresh?->payment_status ?? $order->payment_status,
                'status' => $fresh?->status ?? $order->status,
            ];
        });
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

            $normalizedType = strtolower($eventType);
            $isFailedEvent = in_array($normalizedType, ['payment.failed', 'payment.expired', 'payment.canceled'], true);
            $providerPaymentId = (string) ($data['provider_payment_id'] ?? '');
            $paymentTx = PaymentTransaction::where('order_id', $order->id)
                ->where('provider', $provider)
                ->lockForUpdate()
                ->first();

            if (isset($data['amount']) && $data['amount'] !== null) {
                $expectedAmount = (float) ($paymentTx?->amount ?? $order->total_price);
                if (abs((float) $data['amount'] - $expectedAmount) > 0.01) {
                    throw ValidationException::withMessages([
                        'amount' => ['Webhook amount mismatch'],
                    ]);
                }
            }

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
                $this->markPaid($order, $paymentTx, $provider);
            } elseif ($isFailedEvent) {
                $this->markFailedOrExpired($order, $paymentTx, $normalizedType, $provider);
            } elseif (in_array($normalizedType, ['payment.refunded'], true)) {
                $this->markRefunded($order, $paymentTx);
            }

            if ($paymentTx && $paymentTx->exists) {
                $paymentTx->last_event_at = now();
                $paymentTx->save();
            }

            $event->processed_at = now();
            $event->save();

            $freshOrder = $order->fresh();

            return [
                'duplicate' => false,
                'event_id' => $eventId,
                'order_id' => $order->id,
                'status' => $freshOrder?->status ?? $order->status,
                'payment_status' => $freshOrder?->payment_status ?? $order->payment_status,
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

    private function generateKhrqrQrData(Order $order, string $currencyIso, float $amount): array
    {
        $merchantConfig = $this->resolveKhrqrMerchantConfig();
        $currencyCode = $this->toKhrqrCurrency($currencyIso);

        $individualInfo = new IndividualInfo(
            bakongAccountID: $merchantConfig['account_id'],
            merchantName: $merchantConfig['merchant_name'],
            merchantCity: $merchantConfig['merchant_city'],
            acquiringBank: $merchantConfig['acquiring_bank'],
            accountInformation: $merchantConfig['account_information'] ?? null,
            currency: $currencyCode,
            amount: $amount,
            billNumber: (string) $order->id,
            storeLabel: $merchantConfig['store_label'] ?? null,
            terminalLabel: $merchantConfig['terminal_label'] ?? null,
            mobileNumber: $merchantConfig['mobile_number'] ?? null,
        );



        try {
            $response = BakongKHQR::generateIndividual($individualInfo);
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

        if (!BakongKHQR::verify($qr)->isValid) {
            throw ValidationException::withMessages([
                'khrqr' => ['Generated KHQR payload is invalid'],
            ]);
        }

        return [$qr, $md5];
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function resolvePaymentAmount(string $promo_code, float $baseAmount, string $targetCurrency, float $province_fee): array
    {
        $settings = $this->appSettingRepository->current();
        $baseCurrency = strtoupper((string) ($settings->default_currency_code ?? 'USD'));
        $targetCurrency = strtoupper(trim($targetCurrency));
        $exchangeRate = (float) ($settings->exchange_rate ?? 0);
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }


        $voucher = !empty($promo_code) ? $this->promo_voucher->applyVoucher($customer->id, $promo_code, $baseAmount) : null;
        \Log::info('province_fee');
        \Log::info($province_fee);
        \Log::info('voucher');
        \Log::info($voucher);
        if ($targetCurrency === '') {
            return [round(($baseAmount + $province_fee), 2), $baseCurrency];
        }


        if (!in_array($targetCurrency, ['USD', 'KHR'], true)) {
            throw ValidationException::withMessages([
                'currency' => ['Unsupported KHQR currency: ' . $targetCurrency],
            ]);
        }

        if ($targetCurrency === $baseCurrency) {
            \Log::info($baseAmount);
            \Log::info($voucher);
            // \Log::info((float) ($voucher ? $voucher['voucher']->discount_value : 0));
            return [round(($baseAmount + $province_fee) - (float) ($voucher ? $voucher['voucher']->discount_value : 0), 2), $baseCurrency];
        }

        if ($exchangeRate <= 0) {
            throw ValidationException::withMessages([
                'currency' => ['Exchange rate is not configured'],
            ]);
        }

        if ($baseCurrency === 'USD' && $targetCurrency === 'KHR') {
            return [round((($baseAmount + $province_fee) - (float) ($voucher ? $voucher['voucher']->discount_value : 0)) * $exchangeRate, 2), $baseCurrency];
        }

        if ($baseCurrency === 'KHR' && $targetCurrency === 'USD') {
            return [round((($baseAmount + $province_fee) - (float) ($voucher ? $voucher['voucher']->discount_value : 0)) / $exchangeRate, 2), $baseCurrency];
        }

        throw ValidationException::withMessages([
            'currency' => ['Unsupported currency conversion: ' . $baseCurrency . ' to ' . $targetCurrency],
        ]);
    }


    private function generateKhrqrDeepLink(string $qrString): ?string
    {
        if ($qrString === '') {
            return null;
        }

        $deeplinkUrl = trim((string) (config('payment.providers.khrqr.deeplink_url') ?? ''));
        $appIconUrl = trim((string) (config('payment.providers.khrqr.deeplink_app_icon_url') ?? ''));
        $appName = trim((string) (config('payment.providers.khrqr.deeplink_app_name') ?? ''));
        $appCallback = trim((string) (config('payment.providers.khrqr.deeplink_app_deep_link_callback') ?? ''));

        if ($deeplinkUrl === '' || $appIconUrl === '' || $appName === '' || $appCallback === '') {
            return null;
        }

        try {
            $response = BakongKHQR::generateDeepLinkWithUrl(
                $deeplinkUrl,
                $qrString,
                new SourceInfo($appIconUrl, $appName, $appCallback)
            );
        } catch (KHQRException) {
            return null;
        }

        $data = is_array($response->data ?? null) ? $response->data : [];
        $shortLink = (string) ($data['shortLink'] ?? '');

        return $shortLink !== '' ? $shortLink : null;
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

    private function postKhrqrPoll(PaymentTransaction $transaction, string $pollHash): array
    {
        $response = $this->checkKhrqrTransactionByMd5((string) $transaction->khqr_md5);

        return [
            'poll_hash' => $pollHash,
            'response' => $response,
        ];
    }

    private function checkKhrqrTransactionByMd5(string $md5): array
    {
        $token = (string) (config('payment.providers.khrqr.use_sit') ? config('payment.providers.khrqr.token_sit') : config('payment.providers.khrqr.token'));
        if ($token === '') {
            throw ValidationException::withMessages([
                'provider' => ['KHQR API token is not configured'],
            ]);
        }

        $bakongKhqr = new BakongKHQR($token);

        return $bakongKhqr->checkTransactionByMD5($md5, (bool) config('payment.providers.khrqr.use_sit'));
    }

    private function normalizeKhrqrCheckResponse(array $response, PaymentTransaction $transaction): array
    {
        $responseCode = (int) ($response['responseCode'] ?? 1);
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];

        if ($responseCode !== 0) {
            return [
                'event_id' => (string) ($transaction->khqr_md5 ?: $transaction->provider_payment_id),
                'event_type' => '',
                'data' => [
                    'order_id' => $transaction->order_id,
                    'provider_payment_id' => $transaction->provider_payment_id,
                    'amount' => (float) $transaction->amount,
                    'currency' => $transaction->currency,
                ],
            ];
        }

        return [
            'event_id' => (string) ($transaction->khqr_md5 ?: $transaction->provider_payment_id),
            'event_type' => 'payment.succeeded',
            'data' => [
                'order_id' => $transaction->order_id,
                'provider_payment_id' => (string) ($data['externalRef'] ?? $transaction->provider_payment_id),
                'amount' => isset($data['amount']) ? (float) $data['amount'] : (float) $transaction->amount,
                'currency' => (string) ($data['currency'] ?? $transaction->currency),
            ],
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

    private function markPaid(Order $order, ?PaymentTransaction $paymentTx, string $provider): void
    {

        if ($paymentTx) {
            $paymentTx->status = 'paid';
        }

        if ($order->payment_status === 'paid') {
            return;
        }

        $this->orderLifecycleRepository->transition($order, 'processing', [
            'action_type' => 'payment_confirmed',
            'reason' => 'Payment confirmed by ' . $provider . ' webhook',
            'actor_name' => ucfirst($provider) . ' webhook',
        ]);

        if ($order->payment_method === 'khqr') {
            $order->payment_status = 'paid';
            $order->save();
            $cart = $order->customer?->cart;
            if ($cart) {
                $cart->items()->delete();
            }

            $this->orderRealtimeAlertService->notifyAdminOrderCreated($order->fresh([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product:id,name,slug',
                'items.variant.size:id,name',
                'voucher:id,code,name',
                'paymentTransactions',
                'statusHistories',
            ]));
        }

        if (config('services.telegram-bot-api.send_inline')) {
            DB::afterCommit(function () use ($order) {
                app(TelegramSendingService::class)->sendPaidOrderInvoice($order->fresh());
            });
            return;
        }

        SendingInvoiceTelegramJob::dispatch($order->id)->afterCommit();
    }

    private function markFailedOrExpired(Order $order, ?PaymentTransaction $paymentTx, string $eventType, string $provider): void
    {
        if (in_array($order->payment_status, ['refunded', 'cancelled', 'expired'], true)) {
            return;
        }

        $failedStatus = str_contains($eventType, 'expired') ? 'expired' : (str_contains($eventType, 'canceled') ? 'canceled' : 'failed');
        $fromStatus = $order->status ?: 'pending';
        if ($paymentTx) {
            $paymentTx->status = $failedStatus;
        }

        if ($order->payment_status !== 'paid') {
            $order->payment_status = $failedStatus;
            $order->payment_status = 'failed';
            $order->status = 'cancelled';
            $order->cancelled_at = $order->cancelled_at ?? now();
            $order->save();
            $this->orderLifecycleRepository->recordStatusHistory($order, $fromStatus, 'cancelled', [
                'action_type' => 'payment_failed',
                'reason' => "Payment {$failedStatus} by {$provider}",
                'actor_name' => ucfirst($provider) . ' webhook',
            ]);
            $this->orderLifecycleRepository->restoreStockIfNeeded($order);
        }

        // Requirement: do not keep payment transaction rows for failed/expired/canceled payments.
        if ($paymentTx && $paymentTx->exists) {
            $paymentTx->delete();
        }

        // Remove KHQR orders after failure so customers can re-checkout from cart.
        if ($provider === 'khrqr' && $order->payment_method === 'khqr' && $order->payment_status !== 'paid') {
            $order->delete();
        }
    }

    private function markRefunded(Order $order, ?PaymentTransaction $paymentTx): void
    {
        if ($paymentTx) {
            $paymentTx->status = 'refunded';
        }
        $fromStatus = $order->status ?: 'pending';
        $order->payment_status = 'refunded';
        $order->payment_status = 'failed';
        $order->status = 'refunded';
        $order->refunded_at = $order->refunded_at ?? now();
        $order->save();
        $this->orderLifecycleRepository->recordStatusHistory($order, $fromStatus, 'refunded', [
            'action_type' => 'payment_refunded',
            'reason' => 'Payment refunded by provider',
            'actor_name' => 'Payment provider',
        ]);
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

            if ($order->payment_status === 'paid') {
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

            if ($order->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'order_id' => ['Order already paid'],
                ]);
            }

            $this->orderLifecycleRepository->restoreStockIfNeeded($order);
            $order->delete();
        });
    }
}
