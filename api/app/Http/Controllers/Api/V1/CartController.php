<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Cart\CartAddItemRequest;
use App\Http\Requests\Api\V1\Cart\CartUpdateItemRequest;
use App\Http\Controllers\Controller;
use App\Repositories\CartRepository;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CartRepository $cartRepository)
    {
    }

    public function index()
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $this->cartRepository->getCurrentCart($customer->id);
        return $this->success($payload, 'Cart fetched', 200);
    }

    public function addItem(CartAddItemRequest $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $data = $request->validated();
        try {
            $payload = $this->cartRepository->addItem(
                $customer->id,
                (int) $data['variant_id'],
                (int) $data['quantity']
            );
        } catch (ValidationException $e) {
            return $this->error('Unable to add item', 422, $e->errors());
        }

        return $this->success($payload, 'Item added to cart', 200);
    }

    public function updateItem(CartUpdateItemRequest $request, int $variantId)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $data = $request->validated();
        try {
            $payload = $this->cartRepository->updateItem($customer->id, $variantId, (int) $data['quantity']);
        } catch (ModelNotFoundException) {
            return $this->error('Variant not found', 404);
        } catch (ValidationException $e) {
            return $this->error('Requested quantity exceeds stock', 422, [
                ...$e->errors(),
            ]);
        }

        if (!$payload) {
            return $this->error('Cart item not found', 404);
        }

        return $this->success($payload, 'Cart item updated', 200);
    }

    public function removeItem(int $variantId)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $this->cartRepository->removeItem($customer->id, $variantId);
        if (!$payload) {
            return $this->error('Cart item not found', 404);
        }

        return $this->success($payload, 'Item removed from cart', 200);
    }

    public function clear()
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $this->cartRepository->clear($customer->id);

        return $this->success($payload, 'Cart cleared', 200);
    }
}
