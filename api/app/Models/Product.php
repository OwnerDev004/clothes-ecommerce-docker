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
        "sub_category_id",
        "brand_id",
    ];

    protected $casts = [
        'average_rating' => 'float',
        'total_rating_sum' => 'integer',
        'total_reviews' => 'integer',
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

    public function collections()
    {
        return $this->belongsToMany(Collection::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class);
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

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function faqs()
    {
        return $this->hasMany(ProductFaq::class);
    }



}
