<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope(app(TenantContext::class)));

        static::creating(function (Model $model): void {
            if ($model->getAttribute('organization_id') !== null) {
                return;
            }

            $organizationId = app(TenantContext::class)->getOrganizationId();

            if ($organizationId !== null) {
                $model->setAttribute('organization_id', $organizationId);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
