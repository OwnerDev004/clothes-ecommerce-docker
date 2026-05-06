<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use Sluggable;

    protected $fillable = [
        'name',
        'desc',
        'status',
        'is_system',
        'slug',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_system' => 'boolean',
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

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }
}
