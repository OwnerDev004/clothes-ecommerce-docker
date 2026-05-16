<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\OrderEditRequest;
use App\Http\Requests\Api\V1\Order\OrderStatusUpdateRequest;
use App\Models\User;
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

    #[OA\Get(
        path: '/api/v1/admin/orders',
        tags: ['Admin/Orders'],
        summary: 'Get admin orders',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Admin orders'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index()
    {
        $orders = $this->orderLifecycleRepository->listForAdmin(request()->all());
        return $this->paginate($orders, 'Admin orders');
    }

    #[OA\Get(
        path: '/api/v1/admin/orders/{id}',
        tags: ['Admin/Orders'],
        summary: 'Get admin order detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order detail'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function show(int $id)
    {
        $order = $this->orderLifecycleRepository->findForAdmin($id);
        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $this->success($order, 'Order detail', 200);
    }

    #[OA\Patch(
        path: '/api/v1/admin/orders/{id}/status',
        tags: ['Admin/Orders'],
        summary: 'Update order status',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['order_confirming', 'payment_confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']),
                    new OA\Property(property: 'order_note', type: 'string', maxLength: 500, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Order status updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateStatus(OrderStatusUpdateRequest $request, int $id)
    {
        try {
            $admin = auth()->guard('admin')->user();
            $order = $this->orderLifecycleRepository->updateStatusByAdmin(
                $id,
                (string) $request->validated()['status'],
                [
                    'reason' => $request->validated()['order_note'] ?? null,
                    'actor_type' => $admin ? User::class : null,
                    'actor_id' => $admin?->id,
                    'actor_name' => $admin ? trim((string) ($admin->first_name . ' ' . $admin->last_name)) ?: ($admin->user_name ?: $admin->email) : null,
                    'action_type' => 'manual_status_update',
                ]
            );
        } catch (ValidationException $e) {
            return $this->error('Unable to update status', 422, $e->errors());
        }

        return $this->success($order, 'Order status updated', 200);
    }

    #[OA\Patch(
        path: '/api/v1/admin/orders/{id}',
        tags: ['Admin/Orders'],
        summary: 'Update order details',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'shipping_province', type: 'string', maxLength: 100),
                    new OA\Property(property: 'shipping_address', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'shipping_phone', type: 'string', maxLength: 30, nullable: true),
                    new OA\Property(property: 'shipping_fee', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'order_note', type: 'string', maxLength: 500, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Order updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateOrder(OrderEditRequest $request, int $id)
    {
        try {
            $order = $this->orderLifecycleRepository->updateOrderDetails($id, $request->validated());
        } catch (ValidationException $e) {
            return $this->error('Unable to update order information', 422, $e->errors());
        }

        return $this->success($order, 'Order information updated', 200);
    }
}
