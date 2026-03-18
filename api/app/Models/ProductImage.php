<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductImage extends Model
{
    protected $fillable = [
        "product_id",
        "image_url",
        "cloudinary_public_id",
        "image_type",
        "sort_order"
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute($value): ?string
    {
        $publicId = (string) ($this->attributes['cloudinary_public_id'] ?? '');
        if ($publicId === '') {
            return $value;
        }

        $cloudUrl = (string) config('cloudinary.cloud_url', '');
        $cloudName = $this->extractCloudName($cloudUrl);
        if ($cloudName === null) {
            return $value;
        }

        // Product list cards use thumbnail images. Keep a fixed ratio.
        // Gallery/detail prefers full visibility without hard crop.
        $transformation = $this->image_type === 'thumbnail'
            ? 'c_fill,g_auto,w_640,h_800,f_auto,q_auto'
            : 'c_limit,w_1400,h_1400,f_auto,q_auto';

        return sprintf(
            'https://res.cloudinary.com/%s/image/upload/%s/%s',
            $cloudName,
            $transformation,
            ltrim($publicId, '/')
        );
    }

    private function extractCloudName(string $cloudUrl): ?string
    {
        if ($cloudUrl === '' || !Str::contains($cloudUrl, '@')) {
            return null;
        }

        $parts = explode('@', $cloudUrl);
        $cloudName = trim((string) end($parts));

        return $cloudName !== '' ? $cloudName : null;
    }
}
