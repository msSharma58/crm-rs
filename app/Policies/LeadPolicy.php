<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    use ChecksOrganizationAccess;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('leads.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->sameOrganization($user, $lead)
            && ($user->isSuperAdmin() || $user->can('leads.view'));
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->sameOrganization($user, $lead)
            && ($user->isSuperAdmin() || $user->can('leads.update'));
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->sameOrganization($user, $lead)
            && ($user->isSuperAdmin() || $user->can('leads.delete'));
    }
}
