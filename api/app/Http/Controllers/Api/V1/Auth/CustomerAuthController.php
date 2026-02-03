<?php
namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\CustomerLoginRequest;
use App\Http\Requests\Api\V1\Auth\CustomerRegisterRequest;
use App\Repositories\CustomerRepository;
use App\Traits\ApiResponse;

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
    public function login(CustomerLoginRequest $request){
           

    }
    
}


