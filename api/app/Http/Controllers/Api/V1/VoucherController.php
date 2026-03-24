<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\CartRepository;
use App\Repositories\VoucherRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class VoucherController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly VoucherRepository $voucherRepository,
        private readonly CartRepository $cartRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/v1/vouchers',
        tags: ['Vouchers'],
        summary: 'Get active vouchers',
        responses: [
            new OA\Response(response: 200, description: 'Active vouchers'),
        ]
    )]
    public function index()
    {
        $vouchers = $this->voucherRepository->getActiveForCustomer();

        return $this->success($vouchers, 'Active vouchers', 200);
    }

    #[OA\Get(
        path: '/api/v1/vouchers/signup-offer',
        tags: ['Vouchers'],
        summary: 'Get signup offer voucher',
        responses: [
            new OA\Response(response: 200, description: 'Signup offer'),
        ]
    )]
    public function signupOffer()
    {
        $voucher = $this->voucherRepository->getActiveSignupOffer();
        if (!$voucher) {
            return $this->success(null, 'Signup offer unavailable', 200);
        }

        return $this->success([
            'id' => $voucher->id,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'discount_type' => $voucher->discount_type,
            'discount_value' => (float) $voucher->discount_value,
            'first_order_only' => (bool) $voucher->first_order_only,
            'expires_at' => optional($voucher->expires_at)->toDateString(),
        ], 'Signup offer', 200);
    }

    #[OA\Post(
        path: '/api/v1/vouchers/validate',
        tags: ['Vouchers'],
        summary: 'Validate voucher code',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Voucher valid'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Voucher invalid'),
        ]
    )]
    public function validateVoucher(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $subtotal = $this->getCartSubtotal($customer->id);
            $result = $this->voucherRepository->validateAndCompute(
                $customer->id,
                (string) trim($payload['code']),
                $subtotal
            );
        } catch (ValidationException $e) {
            return $this->error('Voucher invalid', 422, $e->errors());
        }

        return $this->success([
            'voucher' => [
                'id' => $result['voucher']->id,
                'code' => $result['voucher']->code,
                'name' => $result['voucher']->name,
                'discount_type' => $result['voucher']->discount_type,
                'discount_value' => (float) $result['voucher']->discount_value,
            ],
            'cart_subtotal' => $subtotal,
            'discount' => (float) $result['discount'],
            'subtotal_after_discount' => round($subtotal - (float) $result['discount'], 2),
        ], 'Voucher valid', 200);
    }

    #[OA\Post(
        path: '/api/v1/vouchers/apply',
        tags: ['Vouchers'],
        summary: 'Apply voucher code',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Voucher applied'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Voucher invalid'),
        ]
    )]
    public function applyVoucher(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $subtotal = $this->getCartSubtotal($customer->id);
            $result = $this->voucherRepository->applyVoucher(
                $customer->id,
                (string) trim($payload['code']),
                $subtotal
            );
        } catch (ValidationException $e) {
            return $this->error('Voucher invalid', 422, $e->errors());
        }

        return $this->success([
            'voucher' => [
                'id' => $result['voucher']->id,
                'code' => $result['voucher']->code,
                'name' => $result['voucher']->name,
                'discount_type' => $result['voucher']->discount_type,
                'discount_value' => (float) $result['voucher']->discount_value,
            ],
            'cart_subtotal' => $subtotal,
            'discount' => (float) $result['discount'],
            'subtotal_after_discount' => round($subtotal - (float) $result['discount'], 2),
        ], 'Voucher applied', 200);
    }

    private function getCartSubtotal(int $customerId): float
    {
        $cart = $this->cartRepository->getCurrentCart($customerId);
        $subtotal = (float) ($cart['subtotal'] ?? 0);

        if ($subtotal <= 0) {
            throw ValidationException::withMessages([
                'cart' => ['Cart is empty'],
            ]);
        }

        return round($subtotal, 2);
    }
}
