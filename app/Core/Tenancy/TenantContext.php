<?php

declare(strict_types=1);

namespace App\Core\Tenancy;

final class TenantContext
{
    private ?int $organizationId = null;

    public function setOrganizationId(?int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function getOrganizationId(): ?int
    {
        return $this->organizationId;
    }

    public function hasOrganization(): bool
    {
        return $this->organizationId !== null;
    }

    public function clear(): void
    {
        $this->organizationId = null;
    }
}
