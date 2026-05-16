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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_variant_id', 'quantity', 'cost_price'],
                properties: [
                    new OA\Property(property: 'product_variant_id', type: 'integer'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    new OA\Property(property: 'cost_price', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'note', type: 'string', maxLength: 500, nullable: true),
                ]
            )
        ),
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

    #[OA\Put(
        path: '/api/v1/admin/purchases/{id}',
        tags: ['Admin/Purchases'],
        summary: 'Update stock purchase',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_variant_id', 'quantity', 'cost_price'],
                properties: [
                    new OA\Property(property: 'product_variant_id', type: 'integer'),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                    new OA\Property(property: 'cost_price', type: 'number', format: 'float', minimum: 0),
                    new OA\Property(property: 'note', type: 'string', maxLength: 500, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Purchase updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Delete(
        path: '/api/v1/admin/purchases/{id}',
        tags: ['Admin/Purchases'],
        summary: 'Delete stock purchase',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Purchase deleted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
