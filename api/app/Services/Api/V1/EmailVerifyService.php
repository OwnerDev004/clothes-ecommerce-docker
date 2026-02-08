<?php

namespace App\Services\Api\V1\Auth;

use App\Repositories\CustomerRepository;
class EmailVerifyService
{

    protected $customerRepository;
    protected $adminRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;

    }

}