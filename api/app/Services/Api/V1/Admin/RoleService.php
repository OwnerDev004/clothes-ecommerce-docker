<?php

namespace App\Services\Api\V1\Admin;

use App\Models\Module;
use App\Models\Role;
use App\Repositories\Admin\RolePermissionRepository;
use App\Repositories\Admin\RoleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly RolePermissionRepository $rolePermissionRepository,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->roleRepository->paginate($filters);
    }

    public function show(Role $role): Role
    {
        return $role->load(['rolePermissions.module:id,name,slug']);
    }

    public function store(array $validated): Role
    {
        return DB::transaction(function () use ($validated) {
            $payload = $this->rolePayload($validated, true);
            $role = $this->roleRepository->create($payload);

            if (!empty($validated['permissions']) && is_array($validated['permissions'])) {
                $this->syncPermissions($role, $validated['permissions']);
            }

            $this->forgetPermissionCache($role->slug);
            return $this->show($role);
        });
    }

    public function update(Role $role, array $validated): Role
    {
        return DB::transaction(function () use ($role, $validated) {
            $previousSlug = $role->slug;
            $this->ensureEditable($role);

            $payload = $this->rolePayload($validated, false);
            $role = $this->roleRepository->update($role, $payload);

            if (array_key_exists('permissions', $validated) && is_array($validated['permissions'])) {
                $this->syncPermissions($role, $validated['permissions']);
            }

            $this->forgetPermissionCache($previousSlug);
            $this->forgetPermissionCache($role->slug);
            return $this->show($role);
        });
    }

    public function modify(Role $role, array $validated): Role
    {
        return $this->update($role, $validated);
    }

    public function destroy(Role $role): void
    {
        $this->ensureEditable($role);
        $this->forgetPermissionCache($role->slug);
        $this->roleRepository->delete($role);
    }

    private function syncPermissions(Role $role, array $permissions): void
    {
        $allowedModuleIds = Module::query()->pluck('id')->map(fn($id) => (int) $id)->all();

        $filtered = array_values(array_filter($permissions, function ($item) use ($allowedModuleIds) {
            $moduleId = (int) ($item['module_id'] ?? 0);

            return $moduleId > 0 && in_array($moduleId, $allowedModuleIds, true);
        }));

        $this->rolePermissionRepository->syncForRole($role, $filtered);
    }

    private function rolePayload(array $validated, bool $includeSystemFlag): array
    {
        $payload = [];

        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }

        if (array_key_exists('desc', $validated)) {
            $payload['desc'] = $validated['desc'];
        }

        if (array_key_exists('status', $validated)) {
            $payload['status'] = (bool) $validated['status'];
        }

        if ($includeSystemFlag && array_key_exists('is_system', $validated)) {
            $payload['is_system'] = (bool) $validated['is_system'];
        }

        return $payload;
    }

    private function ensureEditable(Role $role): void
    {
        if ($role->is_system) {
            abort(422, 'System role cannot be modified.');
        }
    }

    private function forgetPermissionCache(string $roleSlug): void
    {
        $this->roleRepository->forgetPermissionCache($roleSlug);
    }
}
