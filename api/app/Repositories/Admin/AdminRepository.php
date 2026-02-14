<?php

namespace App\Repositories\Admin;

use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;

class AdminRepository extends BaseRepository
{

    public function __construct(User $model)
    {
        parent::__construct($model);

    }

    public function create(array $data)
    {
        // Hash password
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return parent::create($data);

    }

    public function update(array $data, $id)
    {
        $admin = $this->find($id);
        $admin->update($data);
        return $admin;
    }

    public function delete($id)
    {
        $admin = $this->find($id);
        $admin->delete();
        return true;
    }

    // Find admin by email
    public function findByEmail($email)
    {
        $admin = $this->model->where('email', $email)->first();
        return $admin;
    }

    // Find admin by phone
    public function findByPhone($phone)
    {
        $admin = $this->model->where('phone', $phone)->first();
        return $admin;
    }

    // Find admin by username
    public function findByUsername($user_name)
    {
        $admin = $this->model->where('user_name', $user_name)->first();
        return $admin;
    }


    // Find admin by Id
    public function findById($id)
    {
        $admin = $this->model->find($id);
        return $admin;
    }



}
