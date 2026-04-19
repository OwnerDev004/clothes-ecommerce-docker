<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Customer\ListCustomerRequest;
use App\Http\Requests\Api\V1\Admin\Customer\StoreCustomerRequest;
use App\Http\Requests\Api\V1\Admin\Customer\UpdateCustomerRequest;

use App\Http\Resources\Api\V1\Admin\CustomerResource;
use App\Models\Customer;
use App\Traits\ApiResponse;
use CustomerService;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CustomerService $customerService)
    {

    }

    //index
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
    public function show(Customer $customer)
    {
        $customer = $this->customerService->show($customer);
        return $this->success(new CustomerResource($customer), 'Customer detail');
    }

    //store

    public function store(StoreCustomerRequest $request)
    {

        $validated = $request->validated();
        $customer = $this->customerService->store($validated, $request->file('profile'));
        return $this->created(new CustomerResource($customer), 'Customer information');
    }

    // update
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer = $this->customerService->update($customer, $request->validated(), $request->file('profile'));
        return $this->success(new CustomerResource($customer), 'Customer Updated');
    }

    // delete
    public function distroy(Customer $customer)
    {
        $this->customerService->delete($customer);
        return $this->success(null, 'Customer deleted');
    }
}
