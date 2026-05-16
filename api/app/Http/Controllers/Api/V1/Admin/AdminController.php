<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Admin\ListAdminRequest;
use App\Http\Requests\Api\V1\Admin\Admin\StoreAdminRequest;
use App\Http\Requests\Api\V1\Admin\Admin\UpdateAdminRequest;
use App\Http\Resources\Api\V1\Admin\AdminResource;
use App\Models\User;
use App\Models\Role;
use App\Repositories\Admin\AdminRepository;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;

class AdminController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AdminRepository $adminRepository)
    {
    }

    #[OA\Get(
        path: '/api/v1/admin/admins',
        tags: ['Admin/Admins'],
        summary: 'Get admin users list',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Get admin list success'
            ),
            new OA\Response(response: 401, description: 'Unauthorized Customer')

        ]
    )]
    public function index(ListAdminRequest $request)
    {
        $admins = $this->adminRepository->paginate($request->validated());
        $admins->setCollection($admins->getCollection()->map(fn($admin) => AdminResource::make($admin)->resolve()));

        return $this->paginate($admins, 'Admins list');
    }

    #[OA\Get(
        path: '/api/v1/admin/admins/roles',
        tags: ['Admin/Admins'],
        summary: 'Get assignable admin roles',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Get Roles Admin Success'),
            new OA\Response(response: 401, description: 'Unauthorize'),

        ]
    )]
    public function roleOptions()
    {
        $currentAdmin = auth('admin')->user();
        $roles = Role::query()
            ->select('id', 'name', 'slug')
            ->where('status', true)
            ->orderBy('name')
            ->get();

        if (!$currentAdmin || $currentAdmin->role !== 'super_admin') {
            $roles = $roles->filter(fn(Role $role) => $role->slug !== 'super_admin')->values();
        }

        return $this->success($roles, 'Assignable roles');
    }

    #[OA\Get(
        path: '/api/v1/admin/admins/{admin}',
        tags: ['Admin/Admins'],
        summary: 'Get admin detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'admin', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Get Admin Detail Success'),
            new OA\Response(response: 404, description: 'Not found Admin'),
            new OA\Response(response: 401, description: 'Unauthorize'),

        ]
    )]
    public function show(User $admin)
    {
        $this->ensureAdminRole($admin);

        return $this->success(new AdminResource($admin), 'Admin detail');
    }

    #[OA\Post(
        path: '/api/v1/admin/admins',
        tags: ['Admin/Admins'],
        summary: 'Create admin user',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['first_name', 'last_name', 'gender', 'user_name', 'email', 'password', 'role'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'last_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                    new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'user_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                    new OA\Property(property: 'role', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Store Admin Success'),
            new OA\Response(response: 401, description: 'Unauthorize'),
        ]
    )]
    public function store(StoreAdminRequest $request)
    {
        $payload = $this->normalizePayload($request->validated());
        $this->ensureRoleAssignable($payload['role'] ?? null);

        $admin = $this->adminRepository->create($payload);

        return $this->created(new AdminResource($admin), 'Admin created');
    }

    #[OA\Put(
        path: '/api/v1/admin/admins/{admin}',
        tags: ['Admin/Admins'],
        summary: 'Update admin user',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'admin', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'last_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                    new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'user_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'password', type: 'string', minLength: 6, nullable: true),
                    new OA\Property(property: 'role', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success Update Admin')
        ]
    )]
    public function update(UpdateAdminRequest $request, User $admin)
    {
        $this->ensureAdminRole($admin);
        $this->ensureEditable($admin);

        $payload = $this->normalizePayload($request->validated());
        $this->ensureRoleAssignable($payload['role'] ?? null);

        $admin = $this->adminRepository->update($payload, $admin->id);

        return $this->success(new AdminResource($admin), 'Admin updated');
    }

    #[OA\Delete(
        path: '/api/v1/admin/admins/{admin}',
        tags: ['Admin/Admins'],
        summary: 'Delete admin user',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'admin', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Admin deleted'),
            new OA\Response(response: 404, description: 'Admin not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function destroy(User $admin)
    {
        $this->ensureAdminRole($admin);
        $this->ensureEditable($admin);

        $currentAdmin = auth('admin')->user();
        if ($currentAdmin && $currentAdmin->id === $admin->id) {
            return $this->error('You cannot delete your own admin account.', 422);
        }

        $this->adminRepository->delete($admin->id);

        return $this->success(null, 'Admin deleted');
    }

    private function normalizePayload(array $validated): array
    {
        if (array_key_exists('phone', $validated) && $validated['phone'] === '') {
            $validated['phone'] = null;
        }

        if (array_key_exists('password', $validated) && $validated['password'] === '') {
            unset($validated['password']);
        }

        return $validated;
    }

    private function ensureAdminRole(User $admin): void
    {
        if (!Role::query()->where('slug', $admin->role)->exists()) {
            abort(404);
        }
    }

    private function ensureEditable(User $admin): void
    {
        $currentAdmin = auth('admin')->user();

        if ($admin->role === 'super_admin' && (!$currentAdmin || $currentAdmin->role !== 'super_admin')) {
            abort(403, 'Forbidden.');
        }
    }

    private function ensureRoleAssignable(mixed $roleSlug): void
    {
        $currentAdmin = auth('admin')->user();
        if ($currentAdmin && $currentAdmin->role === 'super_admin') {
            return;
        }

        if ($roleSlug === 'super_admin') {
            abort(403, 'Forbidden.');
        }
    }
}
