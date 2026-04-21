<?php

namespace App\Services\Api\V1\Admin;

use App\Models\Customer;
use App\Repositories\Admin\CustomerRepository;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function __construct(private CustomerRepository $customerRepostitory)
    {

    }

    // Pagination

    public function pagination(array $filters = []): LengthAwarePaginator
    {
        return $this->customerRepostitory->pagination($filters);
    }

    public function show(Customer $customer): Customer
    {
        return $customer->load([
            'oauthAccounts:id,customer_id,provider,provider_user_id,email,avatar_url,expires_at',
            'cart:id,customer_id',
        ]);

    }

    // Update
    public function update(Customer $customer, $validated, ?UploadedFile $image): Customer
    {
        $payload = [];
        if (array_key_exists('full_name', $validated)) {
            $payload['full_name'] = $validated['full_name'];
        }
        if (array_key_exists('gender', $validated)) {
            $payload['gender'] = $validated['gender'];
        }
        if (array_key_exists('dob', $validated)) {
            $payload['dob'] = $validated['dob'];
        }
        if (array_key_exists('user_name', $validated)) {
            $payload['user_name'] = $validated['user_name'];
        }
        if (array_key_exists('email', $validated)) {
            $payload['email'] = $validated['email'];
        }
        if (array_key_exists('phone', $validated)) {
            $payload['phone'] = $validated['phone'];
        }
        if (array_key_exists('address', $validated)) {
            $payload['address'] = $validated['address'];
        }
        if (array_key_exists('telegram_username', $validated)) {
            $payload['telegram_username'] = $validated['telegram_username'];
        }
        if (array_key_exists('enable_telegram_alerts', $validated)) {
            $payload['enable_telegram_alerts'] = $validated['enable_telegram_alerts'];
        }
        if (array_key_exists('status', $validated)) {
            $payload['status'] = $validated['status'];
        }


        if (($image || ($validated['remove_image'] ?? false)) && $customer->avatar_public_id) {
            Cloudinary::uploadApi()->destroy($customer->avatar_public_id);
        }

        if ($image) {
            $upload = Cloudinary::uploadApi()->upload(
                $image->getRealPath(),
                [
                    'folder' => 'clothes_ecommerce/customers/profile'
                ]
            );
            $payload['avatar_url'] = $upload['secure_url'] ?? null;
            $payload['avatar_public_id'] = $upload['public_id'] ?? null;
        } else if (($validated['remove_image'] ?? false)) {
            $payload['avatar_url'] = null;
            $payload['avatar_public_id'] = null;
        }
        return $this->customerRepostitory->update($customer, $payload);
    }

    // delete
    public function delete(Customer $customer): void
    {
        if ($customer['avatar_public_id']) {
            Cloudinary::uploadApi()->destroy($customer['avatar_public_id']);
        }
        $customer->update(['status' => false]);
    }

}
