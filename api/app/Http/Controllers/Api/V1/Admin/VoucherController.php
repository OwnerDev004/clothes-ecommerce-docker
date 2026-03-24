<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Voucher\VoucherStoreRequest;
use App\Http\Requests\Api\V1\Voucher\VoucherUpdateRequest;
use App\Repositories\VoucherRepository;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class VoucherController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VoucherRepository $voucherRepository)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/vouchers',
        tags: ['Admin/Vouchers'],
        summary: 'Get vouchers',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Vouchers fetched'),
        ]
    )]
    public function index(Request $request)
    {
        $vouchers = $this->voucherRepository->getAllForAdmin($request->all());
        return $this->success($vouchers, 'Vouchers fetched', 200);
    }

    #[OA\Post(
        path: '/api/v1/admin/vouchers',
        tags: ['Admin/Vouchers'],
        summary: 'Create voucher',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Voucher created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(VoucherStoreRequest $request)
    {
        $voucher = $this->voucherRepository->createForAdmin($request->validated());
        return $this->success($voucher, 'Voucher created', 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/vouchers/{id}',
        tags: ['Admin/Vouchers'],
        summary: 'Get voucher detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Voucher detail'),
            new OA\Response(response: 404, description: 'Voucher not found'),
        ]
    )]
    public function show(int $id)
    {
        try {
            $voucher = $this->voucherRepository->findByIdForAdmin($id);
        } catch (ModelNotFoundException) {
            return $this->error('Voucher not found', 404);
        }

        return $this->success($voucher, 'Voucher detail', 200);
    }

    #[OA\Put(
        path: '/api/v1/admin/vouchers/{id}',
        tags: ['Admin/Vouchers'],
        summary: 'Update voucher',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Voucher updated'),
            new OA\Response(response: 404, description: 'Voucher not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(VoucherUpdateRequest $request, int $id)
    {
        try {
            $voucher = $this->voucherRepository->updateForAdmin($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->error('Voucher not found', 404);
        }

        return $this->success($voucher, 'Voucher updated', 200);
    }

    #[OA\Delete(
        path: '/api/v1/admin/vouchers/{id}',
        tags: ['Admin/Vouchers'],
        summary: 'Delete voucher',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Voucher deleted'),
            new OA\Response(response: 404, description: 'Voucher not found'),
        ]
    )]
    public function destroy(int $id)
    {
        try {
            $this->voucherRepository->deleteForAdmin($id);
        } catch (ModelNotFoundException) {
            return $this->error('Voucher not found', 404);
        }

        return $this->success(null, 'Voucher deleted', 200);
    }
}
