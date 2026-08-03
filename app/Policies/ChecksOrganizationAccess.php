<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

trait ChecksOrganizationAccess
{
    protected function sameOrganization(User $user, object $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! isset($model->organization_id)) {
            return true;
        }

        return $user->organization_id !== null
            && (int) $user->organization_id === (int) $model->organization_id;
    }
}
