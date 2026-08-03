<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    use ChecksOrganizationAccess;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->sameOrganization($user, $customer)
            && ($user->isSuperAdmin() || $user->can('customers.view'));
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->sameOrganization($user, $customer)
            && ($user->isSuperAdmin() || $user->can('customers.update'));
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->sameOrganization($user, $customer)
            && ($user->isSuperAdmin() || $user->can('customers.delete'));
    }
}
