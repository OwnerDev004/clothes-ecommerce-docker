<?php

namespace App\Repositories;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AppSettingRepository
{
    private const CACHE_KEY = 'app:settings:current';

    public function current(): AppSetting
    {
        $fallback = function (): AppSetting {
            return AppSetting::query()->first() ?? AppSetting::query()->create([
                'app_name' => config('app.name'),
                'default_currency_code' => 'USD',
                'shipping_fee' => 0,
                'free_shipping_threshold' => 0,
                'low_stock_threshold' => 20,
                'tax_rate' => 0,
                'shipping_rates' => [],
            ]);
        };

        try {
            return Cache::rememberForever(self::CACHE_KEY, $fallback);
        } catch (Throwable) {
            return $fallback();
        }
    }

    public function update(array $payload): AppSetting
    {
        $setting = AppSetting::query()->first();

        if (!$setting) {
            $setting = new AppSetting();
        }

        $setting->fill($payload);
        $setting->save();

        try {
            Cache::forget(self::CACHE_KEY);
            Cache::forget('admin:dashboard:summary');
        } catch (Throwable) {
            // Ignore cache backend failures so settings updates still persist.
        }

        return $setting->refresh();
    }

    public function forgetCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // Ignore cache backend failures.
        }
    }
}
