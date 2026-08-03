<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class OrganizationScope implements Scope
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = $this->tenantContext->getOrganizationId();

        if ($organizationId === null) {
            return;
        }

        $builder->where($model->getTable().'.organization_id', $organizationId);
    }
}
