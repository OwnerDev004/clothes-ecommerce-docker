<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\CustomerLoginRequest;
use App\Http\Requests\Api\V1\Auth\CustomerRegisterRequest;
use App\Repositories\CustomerRepository;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    use ApiResponse;
    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepo)
    {
       $this->customerRepository = $customerRepo;
        
    }
    
    public function register(CustomerRegisterRequest $request){
        if($this->customerRepository->findByEmail($request->email)){
            return $this->error("Email Exist Please Try aggain", 409);
        }
        if($this->customerRepository->findByUsername($request->user_name)){
            return $this->error("UserName Exist Please Try aggain",409);
        }

          $customer = $this->customerRepository->create($request->validated());
        return $this->created($customer, 'Customer registered successfully');
    }
 public function login(CustomerLoginRequest $request)
{
    // Find customer by username
    $customer = $this->customerRepository->findByUsername($request->user_name);
    
    // Check if customer exists and password is correct
    if (!$customer) {
        return $this->error('Username not found', 401);
    }
    
    if (!Hash::check($request->password, $customer->password)) {
        return $this->error('Invalid password', 401);
    }
    
    // Generate JWT token
    $token = Auth::guard('customer')->login($customer);
    dd($token);
    return $this->success([
        'customer' => $customer->makeHidden(['password', 'created_at', 'updated_at']),
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => auth('customer')->factory()->getTTL() * 60,
    ], 'Login successful');
}
    
    
}


