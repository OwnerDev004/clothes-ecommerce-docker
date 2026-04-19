<?php

namespace App\Repositories\Admin;

use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository
{

    public function pagination(array $filters = []): LengthAwarePaginator
    {
        $query = Customer::query()->select('full_name', 'gender', 'dob', 'user_name', 'email', 'phone', 'address', 'avatar_url');
        $search = trim((string) ($filters['search_txt'] ?? ''));
        $sortBy = trim((string) ($filters['sort_by'] ?? 'latest'));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%', $search, '%');
                $q->where('address', 'like', $search, '%');
                $q->where('user_name', 'like', $search, '%');
            });
        }
        match ($sortBy) {
            'oldest' => $query->orderBy('customers.id'),
            'name_asc' => $query->orderBy('customers.full_name'),
            'name_desc' => $query->orderByDesc('customers.full_name'),
            default => $query->orderByDesc('customers.id'),
        };

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query->paginate($perPage);

    }
    //create
    public function create(array $payload): Customer
    {
        return Customer::query()->create($payload);
    }
    //update
    public function update(Customer $customer, array $payload): Customer
    {
        $customer->update($payload);
        return $customer;
    }

    // delete 
    public function delete(Customer $customer)
    {
        $customer->delete();
    }

}