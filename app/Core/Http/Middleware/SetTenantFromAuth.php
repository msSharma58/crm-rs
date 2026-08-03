<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantFromAuth
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $organizationId = null;

        if ($user->isSuperAdmin()) {
            $headerOrgId = $request->header('X-Organization-Id');

            if ($headerOrgId !== null && $headerOrgId !== '') {
                $organizationId = (int) $headerOrgId;
            }
        } elseif ($user->organization_id !== null) {
            $organizationId = (int) $user->organization_id;
        }

        if ($organizationId !== null) {
            $this->tenantContext->setOrganizationId($organizationId);
            $this->permissionRegistrar->setPermissionsTeamId($organizationId);
        }

        return $next($request);
    }
}
