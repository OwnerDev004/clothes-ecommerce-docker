<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Admin\ListRoleRequest;
use App\Http\Requests\Api\V1\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Api\V1\Admin\Role\UpdateRoleRequest;
use App\Http\Resources\Api\V1\Admin\RoleResource;
use App\Models\Role;
use App\Services\Api\V1\Admin\RoleService;
use App\Traits\ApiResponse;

class RoleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RoleService $role_service)
    {
    }

    public function index(ListRoleRequest $request)
    {
        $roles = $this->role_service->paginate($request->validated());
        $roles->setCollection($roles->getCollection()->map(fn($role) => RoleResource::make($role)->resolve()));

        return $this->paginate($roles, 'Roles list');
    }

    public function show(Role $role)
    {
        return $this->success(new RoleResource($this->role_service->show($role)), 'Role detail');
    }

    public function store(StoreRoleRequest $request)
    {
        $role = $this->role_service->store($request->validated());

        return $this->created(new RoleResource($role), 'Role created');
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role = $this->role_service->update($role, $request->validated());

        return $this->success(new RoleResource($role), 'Role updated');
    }

    public function modify(UpdateRoleRequest $request, Role $role)
    {
        $role = $this->role_service->modify($role, $request->validated());

        return $this->success(new RoleResource($role), 'Role updated');
    }

    public function destroy(Role $role)
    {
        $this->role_service->destroy($role);

        return $this->success(null, 'Role deleted');
    }
}
