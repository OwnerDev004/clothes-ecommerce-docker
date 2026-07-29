<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasSlug;

    protected $fillable = [
        'category_id',
        'parent_id',
        'name',
        'slug',
        'des',
        'order_num',
        'status',
        'image_url',
        'image_public_id',
        'level'
    ];

    protected $casts = [
        'category_id' => 'integer',
        'parent_id' => 'integer',
        'order_num' => 'integer',
        'level' => 'integer',
        'status' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            $maxOrder = static::max('order_num');
            $item->order_num = is_null($maxOrder) ? 1 : $maxOrder + 1;
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function parent()
    {
        return $this->belongsTo(SubCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SubCategory::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
