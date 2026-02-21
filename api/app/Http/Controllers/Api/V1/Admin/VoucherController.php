<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Voucher\VoucherStoreRequest;
use App\Http\Requests\Api\V1\Voucher\VoucherUpdateRequest;
use App\Repositories\VoucherRepository;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VoucherRepository $voucherRepository)
    {
    }

    public function index(Request $request)
    {
        $vouchers = $this->voucherRepository->getAllForAdmin($request->all());
        return $this->success($vouchers, 'Vouchers fetched', 200);
    }

    public function store(VoucherStoreRequest $request)
    {
        $voucher = $this->voucherRepository->createForAdmin($request->validated());
        return $this->success($voucher, 'Voucher created', 201);
    }

    public function show(int $id)
    {
        try {
            $voucher = $this->voucherRepository->findByIdForAdmin($id);
        } catch (ModelNotFoundException) {
            return $this->error('Voucher not found', 404);
        }

        return $this->success($voucher, 'Voucher detail', 200);
    }

    public function update(VoucherUpdateRequest $request, int $id)
    {
        try {
            $voucher = $this->voucherRepository->updateForAdmin($id, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->error('Voucher not found', 404);
        }

        return $this->success($voucher, 'Voucher updated', 200);
    }

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
