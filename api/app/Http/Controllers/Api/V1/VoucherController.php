<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\CartRepository;
use App\Repositories\VoucherRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
    public function index()
    {
        $vouchers = $this->voucherRepository->getActiveForCustomer();

        return $this->success($vouchers, 'Active vouchers', 200);
    }

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
