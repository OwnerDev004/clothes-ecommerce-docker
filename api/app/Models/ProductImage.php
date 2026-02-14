<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductImage extends Model
{
    protected $fillable = [
        "product_id",
        "image_url",
        "image_type",
        "sort_order"
    ];

    public function product(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
