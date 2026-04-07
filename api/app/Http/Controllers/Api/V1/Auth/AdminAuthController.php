<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\AdminRegisterRequest;
use App\Http\Requests\Api\V1\Auth\AdminLoginRequest;
use App\Repositories\Admin\AdminRepository;
use App\Traits\ApiResponse;
use Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use OpenApi\Attributes as OA;

class AdminAuthController extends Controller
{
    use ApiResponse;
    protected $adminRepository;

    public function __construct(AdminRepository $adminRepo)
    {
        $this->adminRepository = $adminRepo;

    }

    public function show(Request $request)
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $this->error('Unauthorize', 401);
        }
        $adminByEmail = $this->adminRepository->findByEmail($admin->email);
        return $this->success($adminByEmail, '', 200);
    }

    #[OA\Post(
        path: '/api/v1/admin/register',
        tags: ['Admin/Auth'],
        summary: 'Register admin',
        responses: [
            new OA\Response(response: 201, description: 'Admin registered'),
            new OA\Response(response: 409, description: 'Email or username exists'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(AdminRegisterRequest $request)
    {
        if ($this->adminRepository->findByEmail($request->email)) {
            return $this->error("Email Exist Please Try aggain", 409);
        }
        if ($this->adminRepository->findByUsername($request->user_name)) {
            return $this->error("UserName Exist Please Try aggain", 409);
        }

        $customer = $this->adminRepository->create($request->validated());
        return $this->created($customer, 'User registered successfully');
    }
    #[OA\Post(
        path: '/api/v1/admin/login',
        tags: ['Admin/Auth'],
        summary: 'Login admin',
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Credentials invalid'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $admin = $this->adminRepository->findByEmail($request->email);
        // Generate JWT token
        if (!$admin || !$token = auth('admin')->attempt($credentials)) {
            return $this->error('Credentials invalid', 401);
        }

        return $this->success([
            // 'customer' => $customer->makeHidden(['password', 'created_at', 'updated_at']),
            'admin_data' => $admin,
            'admin_access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('admin')->factory()->getTTL() * 60,
        ], 'Login successful');
    }


}
