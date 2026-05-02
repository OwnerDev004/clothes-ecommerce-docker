<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StockPurchaseStoreRequest;
use App\Models\User;
use App\Repositories\Admin\StockPurchaseRepository;
use App\Traits\ApiResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class StockPurchaseController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly StockPurchaseRepository $stockPurchaseRepository)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/purchases',
        tags: ['Admin/Purchases'],
        summary: 'Get stock purchases',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Purchase list'),
        ]
    )]
    public function index()
    {
        return $this->paginate(
            $this->stockPurchaseRepository->list(request()->all()),
            'Purchase list'
        );
    }

    #[OA\Post(
        path: '/api/v1/admin/purchases',
        tags: ['Admin/Purchases'],
        summary: 'Create stock purchase',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Purchase created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StockPurchaseStoreRequest $request)
    {
        try {
            $admin = auth()->guard('admin')->user();
            $purchase = $this->stockPurchaseRepository->store($request->validated(), $admin instanceof User ? $admin : null);
        } catch (ValidationException $e) {
            return $this->error('Unable to create purchase', 422, $e->errors());
        }

        return $this->success($purchase, 'Purchase created', 200);
    }

    public function update(StockPurchaseStoreRequest $request, int $id)
    {
        try {
            $admin = auth()->guard('admin')->user();
            $purchase = $this->stockPurchaseRepository->update($id, $request->validated(), $admin instanceof User ? $admin : null);
        } catch (ValidationException $e) {
            return $this->error('Unable to update purchase', 422, $e->errors());
        }

        return $this->success($purchase, 'Purchase updated', 200);
    }

    public function destroy(int $id)
    {
        try {
            $this->stockPurchaseRepository->destroy($id);
        } catch (ValidationException $e) {
            return $this->error('Unable to delete purchase', 422, $e->errors());
        }

        return $this->success(null, 'Purchase deleted', 200);
    }
}
