<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'app_name',
        'app_tagline',
        'support_email',
        'support_phone',
        'business_address',
        'currency_code',
        'shipping_fee',
        'free_shipping_threshold',
        'low_stock_threshold',
        'tax_rate',
        'shipping_rates'
    ];

    protected $casts = [
        'shipping_fee' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'low_stock_threshold' => 'integer',
        'shipping_rates' => 'array',
    ];
}
