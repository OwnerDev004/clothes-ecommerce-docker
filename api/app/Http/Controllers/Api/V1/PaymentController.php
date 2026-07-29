<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\CheckoutRequest;
use App\Http\Requests\Api\V1\Payment\CreatePaymentIntentRequest;
use App\Repositories\CheckoutRepository;
use App\Repositories\PaymentRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CheckoutRepository $checkoutRepository,
        private readonly PaymentRepository $paymentRepository
    ) {
    }

    #[OA\Post(
        path: '/api/v1/checkout',
        tags: ['Payments'],
        summary: 'Checkout cart',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['shipping_province'],
                properties: [
                    new OA\Property(property: 'shipping_province', type: 'string', maxLength: 100),
                    new OA\Property(property: 'shipping_address', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'shipping_phone', type: 'string', maxLength: 30, nullable: true),
                    new OA\Property(property: 'payment_method', type: 'string', enum: ['khqr', 'cash_on_delivery'], nullable: true),
                    new OA\Property(property: 'voucher_code', type: 'string', maxLength: 100, nullable: true),
                    new OA\Property(property: 'grand_total', type: 'number', format: 'float', minimum: 0, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Checkout successful'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Checkout failed'),
        ]
    )]
    public function checkout(CheckoutRequest $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $request->validated();

        try {
            $orderData = $this->checkoutRepository->checkout($customer->id, $payload);
        } catch (ValidationException $e) {
            return $this->error('Checkout failed', 422, $e->errors());
        }

        return $this->success($orderData, 'Checkout successful', 201);
    }

    #[OA\Post(
        path: '/api/v1/payments/intent',
        tags: ['Payments'],
        summary: 'Create payment intent',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'provider'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer'),
                    new OA\Property(property: 'provider', type: 'string'),
                    new OA\Property(property: 'currency', type: 'string', enum: ['USD', 'KHR'], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Payment intent created'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Unable to create payment intent'),
        ]
    )]
    public function createIntent(CreatePaymentIntentRequest $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $request->validated();
        try {
            $intent = $this->paymentRepository->createIntent(
                $customer->id,
                (int) $payload['order_id'],
                (string) $payload['provider'],
                strtoupper((string) ($payload['currency'] ?? '')),
                (bool) ($payload['is_complete_coupon'] ?? false),
                (string) ($payload['promo_code'] ?? ''),
                (float) ($payload['fee_province'] ?? 0)
            );
        } catch (ValidationException $e) {
            $this->paymentRepository->cleanupFailedKhqrOrder((int) $payload['order_id']);
            return $this->error('Unable to create payment intent', 422, $e->errors());
        }

        return $this->success($intent, 'Payment intent created', 201);
    }

    #[OA\Get(
        path: '/api/v1/payments/khrqr/check/{hash}',
        tags: ['Payments'],
        summary: 'Check KHQR payment status',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'hash', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'KHQR payment status refreshed'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Unable to check payment status'),
        ]
    )]
    public function checkKhrqrStatus(string $hash)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        try {
            $result = $this->paymentRepository->pollKhrqrPaymentStatus($customer->id, $hash);
            \Log::info($result);
        } catch (ValidationException $e) {
            return $this->error('Unable to check payment status', 422, $e->errors());
        }

        return $this->success($result, 'KHQR payment status refreshed', 200);
    }

    #[OA\Post(
        path: '/api/v1/payments/khrqr/cancel',
        tags: ['Payments'],
        summary: 'Cancel KHQR payment intent',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Payment cancelled'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Unable to cancel payment'),
        ]
    )]
    public function cancelKhrqrIntent(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $request->validate([
            'order_id' => ['required', 'integer'],
        ]);

        try {
            $this->paymentRepository->cancelPendingKhqrOrder((int) $payload['order_id'], (int) $customer->id);
        } catch (ValidationException $e) {
            return $this->error('Unable to cancel payment', 422, $e->errors());
        }

        return $this->success(['order_id' => (int) $payload['order_id']], 'Payment cancelled', 200);
    }

    #[OA\Post(
        path: '/api/v1/payments/webhook/{provider}',
        tags: ['Payments'],
        summary: 'Handle payment provider webhook',
        parameters: [
            new OA\Parameter(name: 'provider', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Webhook processed'),
            new OA\Response(response: 422, description: 'Webhook rejected'),
        ]
    )]
    public function webhook(Request $request, string $provider)
    {
        $signature = (string) ($request->header('X-Payment-Signature')
            ?? $request->header('X-KHQR-Signature')
            ?? $request->header('X-Khqr-Signature')
            ?? '');
        $payload = $request->json()->all();
        $rawBody = (string) $request->getContent();

        try {
            $result = $this->paymentRepository->processWebhook($provider, $payload, $signature, $rawBody);
        } catch (ValidationException $e) {
            return $this->error('Webhook rejected', 422, $e->errors());
        }

        return $this->success($result, 'Webhook processed', 200);
    }

    #[OA\Post(
        path: '/api/v1/payments/simulate-paid',
        tags: ['Payments'],
        summary: 'Simulate order paid status',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Payment simulated'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Unable to simulate payment'),
        ]
    )]
    public function simulatePaid(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $request->validate([
            'order_id' => ['required', 'integer'],
        ]);

        try {
            $result = $this->paymentRepository->simulatePaidForCustomer($customer->id, (int) $payload['order_id']);
        } catch (ValidationException $e) {
            return $this->error('Unable to simulate payment', 422, $e->errors());
        }

        return $this->success($result, 'Payment simulated', 200);
    }
}
