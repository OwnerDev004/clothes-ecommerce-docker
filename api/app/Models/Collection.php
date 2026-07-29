<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasSlug;

    protected $fillable = [
        'category_id',
        'name',
        'desc',
        'sort_order',
        'slug',
        'status',
        'img',
        'image_url',
        'image_public_id',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
