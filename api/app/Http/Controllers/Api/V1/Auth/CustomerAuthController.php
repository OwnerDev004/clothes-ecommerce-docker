<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\CustomerForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\CustomerLoginRequest;
use App\Http\Requests\Api\V1\Auth\CustomerRegisterRequest;
use App\Http\Requests\Api\V1\Auth\CustomerResetPasswordRequest;
use App\Repositories\CustomerRepository;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;


class CustomerAuthController extends Controller
{
    use ApiResponse;
    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepo)
    {
        $this->customerRepository = $customerRepo;

    }

    public function register(CustomerRegisterRequest $request)
    {
        if ($this->customerRepository->findByEmail($request->email)) {
            return $this->error("Email Exist Please Try aggain", 409);
        }
        if ($this->customerRepository->findByUsername($request->user_name)) {
            return $this->error("UserName Exist Please Try aggain", 409);
        }

        $customer = $this->customerRepository->create($request->validated());
        return $this->created($customer, 'Customer registered successfully');
    }
    public function login(CustomerLoginRequest $request)
    {
        $credentials = $request->only('user_name', 'password');
        $customer = $this->customerRepository->findByUsername($request->user_name);
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

    public function forgotPassword(CustomerForgotPasswordRequest $request)
    {
        $customer = $this->customerRepository->findByEmail($request->email);

        if (!$customer) {
            return $this->error('Username not exist', 402);
        }

        $status = Password::broker('customers')->sendResetLink($request->only('email'));

        return response()->json([
            'status' => __($status)
        ], $status === Password::RESET_LINK_SENT ? 200 : 400);


    }



    public function resetPassword(CustomerResetPasswordRequest $request)
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($customer, $password) {
                $customer->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, __($status), 200);
        }

        return $this->error(__($status), 400);
    }

}
