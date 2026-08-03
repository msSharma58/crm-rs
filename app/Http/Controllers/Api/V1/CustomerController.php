<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Modules\Customers\Application\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $this->customerService->paginate(
            $request->only(['search']),
            (int) $request->get('per_page', 15)
        );

        return $this->success(
            CustomerResource::collection($customers)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'location' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'meta' => ['nullable', 'array'],
        ]);

        $customer = $this->customerService->create($data);

        return $this->success(new CustomerResource($customer), 'Customer created', 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return $this->success(new CustomerResource($this->customerService->find($customer->id)));
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['nullable', 'email'],
            'location' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'meta' => ['nullable', 'array'],
        ]);

        $customer = $this->customerService->update($customer, $data);

        return $this->success(new CustomerResource($customer), 'Customer updated');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $this->customerService->delete($customer);

        return $this->success(null, 'Customer deleted');
    }
}
