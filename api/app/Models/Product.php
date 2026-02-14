<?php

namespace App\Models;

use App\Traits\HasSkuAndSlug;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasSkuAndSlug, Sluggable;

    protected $fillable = [
        "sku",
        "slug",
        "name",
        "desc",
        "price",
        "category_id",
        "dress_type_id"
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true,
            ],
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function dressType()
    {
        return $this->belongsTo(DressType::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function thumbnail()
    {
        return $this->hasOne(ProductImage::class)->where('image_type', 'thumbnail');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

}
