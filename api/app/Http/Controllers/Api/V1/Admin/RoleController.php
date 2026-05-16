<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Role\ListRoleRequest;
use App\Http\Requests\Api\V1\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Api\V1\Admin\Role\UpdateRoleRequest;
use App\Http\Resources\Api\V1\Admin\RoleResource;
use App\Models\Role;
use App\Services\Api\V1\Admin\RoleService;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RoleService $role_service)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/roles',
        tags: ['Admin/Roles'],
        summary: 'Get roles list',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Roles list'),
        ]
    )]
    public function index(ListRoleRequest $request)
    {
        $roles = $this->role_service->paginate($request->validated());
        $roles->setCollection($roles->getCollection()->map(fn($role) => RoleResource::make($role)->resolve()));

        return $this->paginate($roles, 'Roles list');
    }

    #[OA\Get(
        path: '/api/v1/admin/roles/{role}',
        tags: ['Admin/Roles'],
        summary: 'Get role detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Role detail'),
            new OA\Response(response: 404, description: 'Role not found'),
        ]
    )]
    public function show(Role $role)
    {
        return $this->success(new RoleResource($this->role_service->show($role)), 'Role detail');
    }

    #[OA\Post(
        path: '/api/v1/admin/roles',
        tags: ['Admin/Roles'],
        summary: 'Create role',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'desc', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'status', type: 'boolean', nullable: true),
                    new OA\Property(property: 'is_system', type: 'boolean', nullable: true),
                    new OA\Property(
                        property: 'permissions',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'module_id', type: 'integer'),
                                new OA\Property(
                                    property: 'permissions',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', enum: ['view', 'create', 'edit', 'delete'])
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Role created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreRoleRequest $request)
    {
        $role = $this->role_service->store($request->validated());

        return $this->created(new RoleResource($role), 'Role created');
    }

    #[OA\Put(
        path: '/api/v1/admin/roles/{role}',
        tags: ['Admin/Roles'],
        summary: 'Update role',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'desc', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'status', type: 'boolean', nullable: true),
                    new OA\Property(
                        property: 'permissions',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'module_id', type: 'integer'),
                                new OA\Property(
                                    property: 'permissions',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', enum: ['view', 'create', 'edit', 'delete'])
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Role updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role = $this->role_service->update($role, $request->validated());

        return $this->success(new RoleResource($role), 'Role updated');
    }

    #[OA\Patch(
        path: '/api/v1/admin/roles/{role}',
        tags: ['Admin/Roles'],
        summary: 'Modify role',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'desc', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'status', type: 'boolean', nullable: true),
                    new OA\Property(
                        property: 'permissions',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'module_id', type: 'integer'),
                                new OA\Property(
                                    property: 'permissions',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', enum: ['view', 'create', 'edit', 'delete'])
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Role updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function modify(UpdateRoleRequest $request, Role $role)
    {
        $role = $this->role_service->modify($role, $request->validated());

        return $this->success(new RoleResource($role), 'Role updated');
    }

    #[OA\Delete(
        path: '/api/v1/admin/roles/{role}',
        tags: ['Admin/Roles'],
        summary: 'Delete role',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Role deleted'),
            new OA\Response(response: 404, description: 'Role not found'),
        ]
    )]
    public function destroy(Role $role)
    {
        $this->role_service->destroy($role);

        return $this->success(null, 'Role deleted');
    }
}
