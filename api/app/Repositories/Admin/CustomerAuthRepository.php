<?php

namespace App\Repositories\Auth;

use App\Models\Customer;

class CustomerAuthRepository{

    protected $model;

    public function __constructor(Customer $model)
    {
        $this->model = $model;

    }

    
    
}