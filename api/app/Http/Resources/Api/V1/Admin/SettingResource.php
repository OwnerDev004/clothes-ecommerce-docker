<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'app_name' => $this->app_name,
            'app_tagline' => $this->app_tagline,
            'support_email' => $this->support_email,
            'support_phone' => $this->support_phone,
            'business_address' => $this->business_address,
            'default_currency_code' => $this->default_currency_code,
            'exchange_rate' => $this->exchange_rate,
            'shipping_fee' => (float) $this->shipping_fee,
            'free_shipping_threshold' => (float) $this->free_shipping_threshold,
            'low_stock_threshold' => (int) $this->low_stock_threshold,
            'tax_rate' => (float) $this->tax_rate,
            'shipping_rates' => is_array($this->shipping_rates) ? $this->shipping_rates : [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
