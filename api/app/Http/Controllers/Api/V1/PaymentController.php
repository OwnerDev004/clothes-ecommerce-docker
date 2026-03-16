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

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CheckoutRepository $checkoutRepository,
        private readonly PaymentRepository $paymentRepository
    ) {
    }

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
                strtoupper((string) ($payload['currency'] ?? 'USD'))
            );
        } catch (ValidationException $e) {
            $this->paymentRepository->cleanupFailedKhqrOrder((int) $payload['order_id']);
            return $this->error('Unable to create payment intent', 422, $e->errors());
        }

        return $this->success($intent, 'Payment intent created', 201);
    }

    public function checkKhrqrStatus(string $hash)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        try {
            $result = $this->paymentRepository->pollKhrqrPaymentStatus($customer->id, $hash);
        } catch (ValidationException $e) {
            return $this->error('Unable to check payment status', 422, $e->errors());
        }

        return $this->success($result, 'KHQR payment status refreshed', 200);
    }

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
