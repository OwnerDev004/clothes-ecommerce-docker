<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'desc' => $this->desc,
            'status' => (bool) $this->status,
            'is_system' => (bool) $this->is_system,
            'slug' => $this->slug,
            'role_permissions_count' => isset($this->role_permissions_count) ? (int) $this->role_permissions_count : null,
            'permissions' => $this->whenLoaded('rolePermissions', function () {
                return $this->rolePermissions->map(fn ($permission) => [
                    'id' => $permission->id,
                    'module_id' => $permission->module_id,
                    'module' => $permission->relationLoaded('module') ? [
                        'id' => $permission->module?->id,
                        'name' => $permission->module?->name,
                        'slug' => $permission->module?->slug,
                    ] : null,
                    'permissions' => $permission->permissions,
                    'created_at' => $permission->created_at,
                    'updated_at' => $permission->updated_at,
                ])->values();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
