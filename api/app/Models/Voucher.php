<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'is_signup_coupon',
        'first_order_only',
        'discount_type',
        'discount_value',
        'minimum_order_amount',
        'max_order',
        'max_uses_per_customer',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_signup_coupon' => 'boolean',
        'first_order_only' => 'boolean',
        'discount_value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'expires_at' => 'date:Y-m-d',
    ];

    public function uses(): HasMany
    {
        return $this->hasMany(VoucherUse::class);
    }
}
