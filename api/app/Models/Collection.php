<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use Sluggable;

    protected $fillable = [
        'category_id',
        'name',
        'desc',
        'sort_order',
        'slug',
        'img',
        'image_url',
        'image_public_id',
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

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
