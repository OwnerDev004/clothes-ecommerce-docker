<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Customer\ListCustomerRequest;
use App\Http\Requests\Api\V1\Admin\Customer\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\Admin\CustomerResource;
use App\Models\Customer;
use App\Services\Api\V1\Admin\CustomerService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Password;
use OpenApi\Attributes as OA;
class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CustomerService $customerService)
    {

    }

    //index
    #[OA\Get(
        path: '/api/v1/admin/customers',
        description: 'Get All Customers',
        tags: ['Admin/Customers'],
        security: [['bearerAuth' => '']],
        parameters: [
            new OA\Parameter(
                name: 'search_txt',
                in: 'query',
                required: false,
                description: 'Search by fullname, address, user_name',
                schema: new OA\Schema(
                    type: 'string',
                    maxLength: 255
                )
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                required: false,
                description: 'Filter by sort field secifict',
                schema: new OA\Schema(
                    type: 'string',
                    default: 'latest',
                    enum: ['latest', 'oldest', 'name_asc', 'name_desc']
                )
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    default: 10,
                    minimum: 1,
                    maximum: 200
                )
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Get All Customers'),
            new OA\Response(response: 401, description: 'Unauthorized Customer')
        ]
    )]
    public function index(ListCustomerRequest $request)
    {
        $validated = $request->validated();
        $customers = $this->customerService->pagination($validated);
        $customers->setCollection(
            $customers->getCollection()->map(fn($customer) => CustomerResource::make($customer)->resolve())
        );
        return $this->paginate($customers, 'Customer list');
    }

    // show

    #[OA\Get(
        path: '/api/v1/admin/customers/{customer}',
        description: 'Get Customer By Id',
        tags: ['Admin/Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                required: true,
                description: 'Customer ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Get Customer By ID'),
            new OA\Response(response: 404, description: 'Customer Undefined By ID')
        ]
    )]
    public function show(Customer $customer)
    {

        $customer = $this->customerService->show($customer);
        return $this->success(new CustomerResource($customer), 'Customer detail');
    }

    #[OA\Post(
        path: '/api/v1/admin/customers/{customer}/send-reset-link',
        description: 'Send a customer password reset link for support purposes.',
        tags: ['Admin/Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                required: true,
                description: 'Customer ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reset link sent'),
            new OA\Response(response: 400, description: 'Customer has no email address'),
            new OA\Response(response: 404, description: 'Customer not found'),
        ]
    )]
    public function sendResetLink(Customer $customer)
    {
        if (empty($customer->email)) {
            return $this->error('Customer does not have an email address.', 400);
        }

        $status = Password::broker('customers')->sendResetLink([
            'email' => $customer->email,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, 'Password reset link sent to customer.');
        }

        return $this->error(__($status), 400);
    }

    #[OA\Put(
        path: '/api/v1/admin/customers/{customer}',
        description: 'Update customer information, including account status.',
        tags: ['Admin/Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                required: true,
                description: 'Customer ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'full_name', type: 'string', nullable: true, minLength: 5, maxLength: 100, example: 'Test Customer'),
                            new OA\Property(property: 'gender', type: 'string', nullable: true, enum: ['male', 'female'], example: 'male'),
                            new OA\Property(property: 'dob', type: 'string', nullable: true, example: '2001-10-04'),
                            new OA\Property(property: 'user_name', type: 'string', maxLength: 100, example: 'test@001'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'test@gmail.com'),
                            new OA\Property(property: 'phone', type: 'string', maxLength: 20, example: '0292929833'),
                            new OA\Property(property: 'address', type: 'string', nullable: true, maxLength: 255, example: 'Phnom Penh'),
                            new OA\Property(property: 'profile', type: 'string', format: 'binary', nullable: true, description: 'Max 5 MB'),
                            new OA\Property(property: 'telegram_username', type: 'string', nullable: true, maxLength: 255, example: 'phnompenh'),
                            new OA\Property(property: 'enable_telegram_alerts', type: 'boolean', example: true),
                            new OA\Property(property: 'status', type: 'boolean', nullable: true, example: true),
                            new OA\Property(property: 'remove_image', type: 'boolean', nullable: true, example: false),
                        ]
                    )
                )
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Customer updated'),
            new OA\Response(response: 404, description: 'Customer not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // update
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer = $this->customerService->update($customer, $request->validated(), $request->file('profile'));
        return $this->success(new CustomerResource($customer), 'Customer Updated');
    }

    #[OA\Delete(
        path: '/api/v1/admin/customers/{customer}',
        description: 'Deactivate a customer by setting status to false.',
        tags: ['Admin/Customers'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'customer',
                in: 'path',
                required: true,
                description: 'Customer ID',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Customer deactivated'),
            new OA\Response(response: 404, description: 'Customer not found'),
        ]
    )]
    // delete
    public function destroy(Customer $customer)
    {
        $this->customerService->delete($customer);
        return $this->success(null, 'Customer deactivated');
    }
}
