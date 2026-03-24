<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\CustomerUpdateRequest;
use App\Http\Requests\Api\V1\Customer\CustomerAvatarRequest;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
// use Cloudinary\Transformation\Resize;
// use Cloudinary\Transformation\Gravity;
use App\Repositories\CustomerRepository;
use App\Traits\ApiResponse;
use OpenApi\Attributes as OA;
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
    #[OA\Get(
        path: '/api/v1/profile',
        tags: ['Customer/Profile'],
        summary: 'Get customer profile',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile fetched',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show()
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error("Unauthorized", 401);
        }
        $profile = $this->customerRepository->findById($customer->id);
        if ($profile) {
            $profile->setAttribute('requires_profile_completion', $profile->requiresProfileCompletion());
        }

        return $this->success($profile, "client profile success", 200);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/v1/profile',
        tags: ['Customer/Profile'],
        summary: 'Update customer profile',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'user_name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'full_name', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female'], nullable: true),
                    new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, nullable: true),
                    new OA\Property(property: 'address', type: 'string', maxLength: 500, nullable: true),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, nullable: true),
                    new OA\Property(property: 'password_confirmation', type: 'string', minLength: 8, nullable: true),
                    new OA\Property(property: 'telegram_username', type: 'string', maxLength: 255, nullable: true),
                    new OA\Property(property: 'enable_telegram_alerts', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Customer updated'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(CustomerUpdateRequest $request)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error("Unauthorized", 401);
        }

        $data = $request->validated();

        $basicFields = Arr::only($data, [
            'full_name',
            'gender',
            'dob',
            'email',
            'phone',
            'address',
        ]);

        if ($basicFields) {
            $customer->fill($basicFields);
        }

        if (array_key_exists('user_name', $data) && $data['user_name'] !== null && $data['user_name'] !== '') {
            $customer->user_name = $data['user_name'];
        }

        if (array_key_exists('password', $data) && $data['password']) {
            $customer->password = Hash::make($data['password']);
        }

        if (array_key_exists('telegram_username', $data)) {
            $customer->telegram_username = $data['telegram_username'];
        }

        if (array_key_exists('enable_telegram_alerts', $data)) {
            $customer->enable_telegram_alerts = (bool) $data['enable_telegram_alerts'];
        }

        $customer->save();

        $customer->setAttribute('requires_profile_completion', $customer->requiresProfileCompletion());

        return $this->success($customer, "Customer Updated", 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Post(
        path: '/api/v1/change_avatar',
        tags: ['Customer/Profile'],
        summary: 'Update customer avatar',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['avatar'],
                    properties: [
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Avatar updated'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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


    #[OA\Post(
        path: '/api/v1/delete_avatar',
        tags: ['Customer/Profile'],
        summary: 'Delete customer avatar',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Avatar deleted'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
