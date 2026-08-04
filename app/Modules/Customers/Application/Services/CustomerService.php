<?php

declare(strict_types=1);

namespace App\Modules\Customers\Application\Services;

use App\Models\Customer;
use App\Models\CustomerFamilyMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CustomerService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::query()
            ->with(['assignee', 'lead'])
            ->latest();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Customer
    {
        return Customer::with(['familyMembers', 'assignee', 'lead', 'bookings'])->find($id);
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->fresh();
    }

    public function delete(Customer $customer): bool
    {
        return (bool) $customer->delete();
    }

    public function addFamilyMember(Customer $customer, array $data): CustomerFamilyMember
    {
        $data['organization_id'] ??= $customer->organization_id;

        return $customer->familyMembers()->create($data);
    }

    public function updateFamilyMember(CustomerFamilyMember $member, array $data): CustomerFamilyMember
    {
        $member->update($data);

        return $member->fresh();
    }

    public function removeFamilyMember(CustomerFamilyMember $member): bool
    {
        return (bool) $member->delete();
    }

    public function assign(Customer $customer, ?int $userId): Customer
    {
        $customer->update(['assigned_to' => $userId]);

        return $customer->fresh();
    }
}
