<?php

namespace App\Repositories\Admin;

use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

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

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->select('id', 'first_name', 'last_name', 'gender', 'dob', 'user_name', 'phone', 'email', 'role', 'created_at', 'updated_at')
            ->whereNotNull('role');

        $search = trim((string) ($filters['search_txt'] ?? ''));
        $sortBy = trim((string) ($filters['sort_by'] ?? 'latest'));

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('user_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhereRaw("concat(first_name, ' ', last_name) ilike ?", ['%' . $search . '%']);
            });
        }

        match ($sortBy) {
            'oldest' => $query->orderBy('id'),
            'name_asc' => $query->orderBy('first_name')->orderBy('last_name'),
            'name_desc' => $query->orderByDesc('first_name')->orderByDesc('last_name'),
            default => $query->orderByDesc('id'),
        };

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query->paginate($perPage);
    }



}
