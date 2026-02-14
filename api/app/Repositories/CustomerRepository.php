<?php

namespace App\Repositories;
use App\Models\Customer;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Hash;

class CustomerRepository extends BaseRepository
{

  public function __construct(Customer $model)
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
    $customer = $this->find($id);
    $customer->update($data);
    return $customer;
  }

  public function delete($id)
  {
    $customer = $this->find($id);
    $customer->delete();
    return true;
  }

  // Find customer by email
  public function findByEmail($email)
  {
    $customer = $this->model->where('email', $email)->first();
    return $customer;
  }

  // Find customer by phone
  public function findByPhone($phone)
  {
    $customer = $this->model->where('phone', $phone)->first();
    return $customer;
  }

  // Find customer by username
  public function findByUsername($user_name)
  {
    $customer = $this->model->where('user_name', $user_name)->first();
    return $customer;
  }


  // Find customer by Id
  public function findById($id)
  {
    $customer = $this->model->find($id);
    return $customer;
  }



}
