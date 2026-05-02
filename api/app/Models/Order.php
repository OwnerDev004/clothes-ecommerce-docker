<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'voucher_id',
        'order_date',
        'subtotal_price',
        'discount_amount',
        'total_price',
        'shipping_province',
        'shipping_fee',
        'shipping_address',
        'shipping_phone',
        'payment_method',
        'payment_provider',
        'payment_reference',
        'payment_expires_at',
        'payment_status',
        'status',
        'paid_at',
        'cancelled_at',
        'refunded_at',
        'stock_restored_at',
        'order_note'
    ];

    protected $casts = [
        'subtotal_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'order_date' => 'date:Y-m-d',
        'payment_expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'stock_restored_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }
}
