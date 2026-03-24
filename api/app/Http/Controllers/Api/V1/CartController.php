<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Cart\CartAddItemRequest;
use App\Http\Requests\Api\V1\Cart\CartUpdateItemRequest;
use App\Http\Controllers\Controller;
use App\Repositories\CartRepository;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CartRepository $cartRepository)
    {
    }

    #[OA\Get(
        path: '/api/v1/cart',
        tags: ['Cart'],
        summary: 'Get current cart',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cart fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index()
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $payload = $this->cartRepository->getCurrentCart($customer->id);
        return $this->success($payload, 'Cart fetched', 200);
    }

    #[OA\Post(
        path: '/api/v1/cart/items',
        tags: ['Cart'],
        summary: 'Add item to cart',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['variant_id', 'quantity'],
                properties: [
                    new OA\Property(property: 'variant_id', type: 'integer'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Item added'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Put(
        path: '/api/v1/cart/items/{variantId}',
        tags: ['Cart'],
        summary: 'Update cart item quantity',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                ]
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'variantId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cart item updated'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Delete(
        path: '/api/v1/cart/items/{variantId}',
        tags: ['Cart'],
        summary: 'Remove cart item',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'variantId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item removed'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
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

    #[OA\Delete(
        path: '/api/v1/cart/clear',
        tags: ['Cart'],
        summary: 'Clear cart',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Cart cleared'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
