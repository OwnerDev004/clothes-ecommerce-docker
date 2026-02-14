<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\CustomerUpdateRequest;
use App\Http\Requests\Api\V1\Customer\CustomerAvatarRequest;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
// use Cloudinary\Transformation\Resize;
// use Cloudinary\Transformation\Gravity;
use App\Repositories\CustomerRepository;
use App\Traits\ApiResponse;
class CustomerController extends Controller
{
    use ApiResponse;


    protected $customerRepository;

    public function __construct(CustomerRepository $customerRepo)
    {
        $this->customerRepository = $customerRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error("Unauthorized", 401);
        }
        $profile = $this->customerRepository->findById($customer->id);

        return $this->success($profile, "client profile success", 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerUpdateRequest $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error("Unauthorized", 401);
        }
        $customer_updated = $this->customerRepository->update($request->validated(), $customer->id);
        return $this->success($customer_updated, "Customer Updated", 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function editAvatar(CustomerAvatarRequest $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error("Unauthorized", 401);
        }
        if ($customer->avatar_public_id) {
            Cloudinary::uploadApi()->destroy($customer->avatar_public_id);
        }

        $upload = Cloudinary::uploadApi()->upload(
            $request->file('avatar')->getRealPath(),
            ['folder' => 'clothes_ecommerce/customer-avatars']
        );
        // Resize
        // $imageUrl = (string) Cloudinary::image($upload['public_id'])
        //     ->resize(Resize::fill(200, 200)->gravity(Gravity::auto()));

        $customer->avatar_url = $upload['secure_url'] ?? null;
        $customer->avatar_public_id = $upload['public_id'] ?? null;


        $customer->save();

        return $this->success($customer, "Avatar updated", 200);
    }


    public function deleteAvatar(Request $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error("Unauthorized", 401);
        }
        if ($customer->avatar_public_id) {
            Cloudinary::uploadApi()->destroy($customer->avatar_public_id);
        }
        $customer->avatar_url = null;
        $customer->avatar_public_id = null;


        $customer->save();

        return $this->success($customer, "Avatar Deleted", 200);
    }
}
