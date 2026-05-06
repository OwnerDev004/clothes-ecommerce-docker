<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolePermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'module_id' => $this->module_id,
            'module' => $this->relationLoaded('module') ? [
                'id' => $this->module?->id,
                'name' => $this->module?->name,
                'slug' => $this->module?->slug,
            ] : null,
            'permissions' => $this->permissions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
