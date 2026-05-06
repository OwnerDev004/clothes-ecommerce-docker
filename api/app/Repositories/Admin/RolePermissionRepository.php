<?php

namespace App\Repositories\Admin;

use App\Models\Role;
use App\Models\RolePermission;

class RolePermissionRepository
{
    public function syncForRole(Role $role, array $modulePermissions): void
    {
        $keepModuleIds = [];

        foreach ($modulePermissions as $item) {
            $moduleId = (int) ($item['module_id'] ?? 0);
            $permissions = $item['permissions'] ?? [];

            if ($moduleId < 1) {
                continue;
            }

            $permissions = array_values(array_unique(array_filter(
                $permissions,
                fn ($permission) => is_string($permission) && $permission !== ''
            )));

            if (empty($permissions)) {
                RolePermission::query()
                    ->where('role_id', $role->id)
                    ->where('module_id', $moduleId)
                    ->delete();
                continue;
            }

            RolePermission::query()->updateOrCreate(
                [
                    'role_id' => $role->id,
                    'module_id' => $moduleId,
                ],
                [
                    'permissions' => $permissions,
                ]
            );

            $keepModuleIds[] = $moduleId;
        }

        if (empty($keepModuleIds)) {
            RolePermission::query()
                ->where('role_id', $role->id)
                ->delete();

            return;
        }

        RolePermission::query()
            ->where('role_id', $role->id)
            ->whereNotIn('module_id', $keepModuleIds)
            ->delete();
    }
}
