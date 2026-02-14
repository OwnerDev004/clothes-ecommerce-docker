<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSkuAndSlug
{
    protected static function bootHasSkuAndSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->sku)) {
                $model->sku = $model->generateUniqueSku();
            }
        });

        static::updating(function ($model) {
            // Keep SKU immutable once created.
            if ($model->isDirty('sku')) {
                $model->sku = $model->getOriginal('sku');
            }
        });
    }

    protected function generateUniqueSku(): string
    {
        do {
            $candidate = 'SKU-' . strtoupper(Str::random(8));
        } while (static::query()->where('sku', $candidate)->exists());

        return $candidate;
    }
}
