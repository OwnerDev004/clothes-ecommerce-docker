<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\AdminRegisterRequest;
use App\Http\Requests\Api\V1\Auth\CustomerLoginRequest;
use App\Http\Requests\Api\V1\Auth\CustomerRegisterRequest;
use App\Repositories\CustomerRepository;
use App\Traits\ApiResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminAuthController extends Controller
{
    use ApiResponse;
    protected $adminRepository;

    public function __construct(CustomerRepository $adminRepo)
    {
        $this->adminRepository = $adminRepo;

    }

    public function register(AdminRegisterRequest $request)
    {
        if ($this->adminRepository->findByEmail($request->email)) {
            return $this->error("Email Exist Please Try aggain", 409);
        }
        if ($this->adminRepository->findByUsername($request->user_name)) {
            return $this->error("UserName Exist Please Try aggain", 409);
        }

        $customer = $this->adminRepository->create($request->validated());
        return $this->created($customer, 'Customer registered successfully');
    }
    public function login(CustomerLoginRequest $request)
    {
        $credentials = $request->only('user_name', 'password');
        $customer = $this->adminRepository->findByUsername($request->user_name);
        // Generate JWT token
        if (!$customer || !$token = auth('customer')->attempt($credentials)) {
            return $this->error('Credentials invalid', 401);
        }

        return $this->success([
            // 'customer' => $customer->makeHidden(['password', 'created_at', 'updated_at']),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('customer')->factory()->getTTL() * 60,
        ], 'Login successful');
    }


}


