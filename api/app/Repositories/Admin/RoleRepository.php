<?php

namespace App\Repositories\Admin;

use App\Models\Role;
use App\Models\Module;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class RoleRepository extends BaseRepository
{
    private const PERMISSION_ACTIONS = ['view', 'create', 'edit', 'delete'];

    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->withCount('rolePermissions')
            ->select('id', 'name', 'desc', 'status', 'is_system', 'slug', 'created_at', 'updated_at');

        $search = trim((string) ($filters['search_txt'] ?? ''));
        $sortBy = trim((string) ($filters['sort_by'] ?? 'latest'));
        $status = $filters['status'] ?? null;

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('roles.name', 'like', '%' . $search . '%')
                    ->orWhere('roles.slug', 'like', '%' . $search . '%')
                    ->orWhere('roles.desc', 'like', '%' . $search . '%');
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('roles.status', filter_var($status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $status);
        }

        match ($sortBy) {
            'oldest' => $query->orderBy('roles.id'),
            'name_asc' => $query->orderBy('roles.name'),
            'name_desc' => $query->orderByDesc('roles.name'),
            default => $query->orderByDesc('roles.id'),
        };

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Role
    {
        return $this->model->newQuery()
            ->with(['rolePermissions.module:id,name,slug'])
            ->find($id);
    }

    public function update(Role $role, array $payload): Role
    {
        $role->update($payload);

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function permissionMatrixForSlug(string $roleSlug): array
    {
        $cacheKey = $this->permissionCacheKey($roleSlug);

        return Cache::rememberForever($cacheKey, function () use ($roleSlug) {
            $role = $this->model->newQuery()
                ->where('slug', $roleSlug)
                ->with(['rolePermissions.module:id,name,slug'])
                ->first();

            if (!$role) {
                return [
                    'role' => null,
                    'is_super_admin' => false,
                    'permission_map' => [],
                    'modules' => [],
                ];
            }

            if ($role->slug === 'super_admin' || $role->is_system) {
                $modules = Module::query()
                    ->select('id', 'name', 'slug', 'created_at', 'updated_at')
                    ->orderBy('name')
                    ->get()
                    ->map(function (Module $module) {
                        return [
                            'id' => $module->id,
                            'name' => $module->name,
                            'slug' => $module->slug,
                            'actions' => self::PERMISSION_ACTIONS,
                        ];
                    })
                    ->values()
                    ->all();

                $permissionMap = [];
                foreach ($modules as $module) {
                    $permissionMap[$module['slug']] = self::PERMISSION_ACTIONS;
                }

                return [
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                        'is_system' => (bool) $role->is_system,
                    ],
                    'is_super_admin' => true,
                    'permission_map' => $permissionMap,
                    'modules' => $modules,
                ];
            }

            $modules = [];
            $permissionMap = [];

            foreach ($role->rolePermissions as $rolePermission) {
                $moduleSlug = $rolePermission->module?->slug;
                if (!$moduleSlug) {
                    continue;
                }

                $actions = array_values(array_intersect(
                    self::PERMISSION_ACTIONS,
                    is_array($rolePermission->permissions) ? $rolePermission->permissions : []
                ));

                $permissionMap[$moduleSlug] = $actions;
                $modules[] = [
                    'id' => $rolePermission->module_id,
                    'name' => $rolePermission->module?->name,
                    'slug' => $moduleSlug,
                    'actions' => $actions,
                ];
            }

            return [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'is_system' => (bool) $role->is_system,
                ],
                'is_super_admin' => false,
                'permission_map' => $permissionMap,
                'modules' => array_values($modules),
            ];
        });
    }

    public function forgetPermissionCache(string $roleSlug): void
    {
        Cache::forget($this->permissionCacheKey($roleSlug));
    }

    private function permissionCacheKey(string $roleSlug): string
    {
        return 'admin:role-permissions:' . $roleSlug;
    }
}
