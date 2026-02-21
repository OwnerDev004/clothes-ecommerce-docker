<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\OrderStatusUpdateRequest;
use App\Repositories\OrderLifecycleRepository;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OrderLifecycleRepository $orderLifecycleRepository)
    {
    }

    public function index()
    {
        $orders = $this->orderLifecycleRepository->listForAdmin(request()->all());
        return $this->paginate($orders, 'Admin orders');
    }

    public function show(int $id)
    {
        $order = $this->orderLifecycleRepository->findForAdmin($id);
        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $this->success($order, 'Order detail', 200);
    }

    public function updateStatus(OrderStatusUpdateRequest $request, int $id)
    {
        try {
            $order = $this->orderLifecycleRepository->updateStatusByAdmin(
                $id,
                (string) $request->validated()['status']
            );
        } catch (ValidationException $e) {
            return $this->error('Unable to update status', 422, $e->errors());
        }

        return $this->success($order, 'Order status updated', 200);
    }
}
