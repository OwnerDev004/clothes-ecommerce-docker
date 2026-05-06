<?php

namespace App\Repositories;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class AppSettingRepository
{
    private const CACHE_KEY = 'app:settings:current';

    public function current(): AppSetting
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return AppSetting::query()->first() ?? AppSetting::query()->create([
                'app_name' => config('app.name'),
                'currency_code' => 'USD',
                'shipping_fee' => 0,
                'free_shipping_threshold' => 0,
                'low_stock_threshold' => 20,
                'tax_rate' => 0,
                'shipping_rates' => [],
            ]);
        });
    }

    public function update(array $payload): AppSetting
    {
        $setting = AppSetting::query()->first();

        if (!$setting) {
            $setting = new AppSetting();
        }

        $setting->fill($payload);
        $setting->save();

        Cache::forget(self::CACHE_KEY);
        Cache::forget('admin:dashboard:summary');

        return $setting->refresh();
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
