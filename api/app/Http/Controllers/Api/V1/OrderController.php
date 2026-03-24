<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\OrderCancelRequest;
use App\Repositories\OrderLifecycleRepository;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OrderLifecycleRepository $orderLifecycleRepository)
    {
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/v1/orders',
        tags: ['Orders'],
        summary: 'Get customer orders',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Customer orders',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
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

        $orders = $this->orderLifecycleRepository->listForCustomer($customer->id, request()->all());
        return $this->paginate($orders, 'Customer orders');
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/v1/orders/{id}',
        tags: ['Orders'],
        summary: 'Get order detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order detail'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function show(int $id)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $order = $this->orderLifecycleRepository->findForCustomer($id, $customer->id);
        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $this->success($order, 'Order detail', 200);
    }

    #[OA\Post(
        path: '/api/v1/orders/{id}/cancel',
        tags: ['Orders'],
        summary: 'Cancel order',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string', maxLength: 255, nullable: true),
                ]
            )
        ),
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order cancelled'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function cancel(OrderCancelRequest $request, int $id)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        try {
            $order = $this->orderLifecycleRepository->cancelByCustomer($id, $customer->id);
        } catch (ValidationException $e) {
            return $this->error('Unable to cancel order', 422, $e->errors());
        }

        return $this->success($order, 'Order cancelled', 200);
    }
}
