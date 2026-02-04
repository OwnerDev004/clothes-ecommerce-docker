<?php

namespace App\Repositories\Admin;

use App\Models\User as Admin;

class AdminRepository{

    protected $model;

    public function __constructor(Admin $model)
    {
        $this->model = $model;

    }

    
    
}