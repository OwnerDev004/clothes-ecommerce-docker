<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Voucher;
use App\Models\VoucherUse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class VoucherRepository
{
    private function activeVoucherQuery()
    {
        return Voucher::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', now()->toDateString());
            });
    }

    public function getActiveForCustomer(): Collection
    {
        return $this->activeVoucherQuery()
            ->orderByDesc('id')
            ->get();
    }

    public function getActiveSignupOffer(): ?Voucher
    {
        return $this->activeVoucherQuery()
            ->where('is_signup_coupon', true)
            ->orderByDesc('id')
            ->first();
    }

    public function getAllForAdmin(array $filters = []): Collection
    {
        $query = Voucher::query();

        $search = trim((string) ($filters['search_txt'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query
            ->withCount('uses')
            ->orderByDesc('id')
            ->get();
    }

    public function findByIdForAdmin(int $id): Voucher
    {
        return Voucher::query()
            ->withCount('uses')
            ->findOrFail($id);
    }

    public function createForAdmin(array $payload): Voucher
    {
        return Voucher::query()->create($this->normalizeAdminPayload($payload));
    }

    public function updateForAdmin(int $id, array $payload): Voucher
    {
        $voucher = Voucher::query()->findOrFail($id);
        $voucher->update($this->normalizeAdminPayload($payload));

        return $voucher->fresh();
    }

    public function deleteForAdmin(int $id): void
    {
        $voucher = Voucher::query()->findOrFail($id);
        $voucher->delete();
    }

    public function validateAndCompute(int $customerId, string $code, float $subtotal): array
    {
        $normalizedCode = strtoupper(trim($code));
        $voucher = Voucher::where('code', $normalizedCode)->first();
        if (!$voucher) {
            throw ValidationException::withMessages([
                'voucher_code' => ['Voucher not found'],
            ]);
        }

        if (!$voucher->is_active) {
            throw ValidationException::withMessages([
                'voucher_code' => ['Voucher is inactive'],
            ]);
        }
        if ($voucher->expires_at && now()->toDateString() > $voucher->expires_at->toDateString()) {
            throw ValidationException::withMessages([
                'voucher_code' => ['Voucher has expired'],
            ]);
        }

        if ((bool) $voucher->first_order_only) {
            $hasPriorOrders = Order::query()
                ->where('customer_id', $customerId)
                ->where(function ($query) {
                    $query->whereNotNull('paid_at')
                        ->orWhere('payment_status', 'paid')
                        ->orWhere('payment_state', 'paid');
                })
                ->exists();

            if ($hasPriorOrders) {
                throw ValidationException::withMessages([
                    'voucher_code' => ['Voucher is only valid for first paid order'],
                ]);
            }
        }

        if ((float) $subtotal < (float) $voucher->minimum_order_amount) {
            throw ValidationException::withMessages([
                'voucher_code' => ['Order does not meet voucher minimum amount'],
            ]);
        }

        if ((int) $voucher->max_order > 0) {
            $globalUsed = VoucherUse::where('voucher_id', $voucher->id)->count();
            if ($globalUsed >= (int) $voucher->max_order) {
                throw ValidationException::withMessages([
                    'voucher_code' => ['Voucher usage limit reached'],
                ]);
            }
        }

        if ((int) $voucher->max_uses_per_customer > 0) {
            $customerUsed = VoucherUse::where('voucher_id', $voucher->id)
                ->where('customer_id', $customerId)
                ->count();
            if ($customerUsed >= (int) $voucher->max_uses_per_customer) {
                throw ValidationException::withMessages([
                    'voucher_code' => ['Voucher customer usage limit reached'],
                ]);
            }
        }

        $discount = 0.0;
        if ($voucher->discount_type === 'percentage') {
            $discount = round($subtotal * ((float) $voucher->discount_value / 100), 2);
        } else {
            $discount = round((float) $voucher->discount_value, 2);
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return [
            'voucher' => $voucher,
            'discount' => round($discount, 2),
        ];
    }

    private function normalizeAdminPayload(array $payload): array
    {
        if (array_key_exists('code', $payload)) {
            $payload['code'] = strtoupper(trim((string) $payload['code']));
        }

        if (array_key_exists('minimum_order_amount', $payload) && $payload['minimum_order_amount'] === null) {
            $payload['minimum_order_amount'] = 0;
        }

        if (array_key_exists('max_order', $payload) && $payload['max_order'] === null) {
            $payload['max_order'] = 0;
        }

        if (array_key_exists('max_uses_per_customer', $payload) && $payload['max_uses_per_customer'] === null) {
            $payload['max_uses_per_customer'] = 0;
        }

        return $payload;
    }

    public function applyVoucher(int $customerId, string $code, float $subtotal): array
    {
        return $this->validateAndCompute($customerId, $code, $subtotal);
    }

}
