<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\OrderCancelRequest;
use App\Repositories\OrderLifecycleRepository;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OrderLifecycleRepository $orderLifecycleRepository)
    {
    }

    /**
     * Display a listing of the resource.
     */
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
