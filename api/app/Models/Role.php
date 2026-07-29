<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasSlug;

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

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }
}
