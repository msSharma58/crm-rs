<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'leads' => ['view', 'create', 'update', 'delete'],
            'customers' => ['view', 'create', 'update', 'delete'],
            'projects' => ['view', 'create', 'update', 'delete'],
            'units' => ['view', 'create', 'update', 'delete'],
            'bookings' => ['view', 'create', 'update', 'delete'],
            'payments' => ['view', 'create', 'update', 'delete'],
            'visits' => ['view', 'create', 'update', 'delete'],
            'follow_ups' => ['view', 'create', 'update', 'delete'],
            'tasks' => ['view', 'create', 'update', 'delete'],
            'documents' => ['view', 'create', 'delete'],
            'campaigns' => ['view', 'create', 'update', 'delete'],
            'users' => ['view', 'create', 'update', 'delete'],
            'reports' => ['view'],
            'dashboard' => ['view'],
            'automations' => ['view', 'create', 'update'],
            'audit_logs' => ['view'],
        ];

        $allPermissions = [];
        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                $allPermissions[] = Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $allPermissionNames = collect($allPermissions)->pluck('name')->all();
        $readOnly = array_values(array_filter(
            $allPermissionNames,
            static fn (string $name): bool => str_ends_with($name, '.view')
        ));

        $roleDefinitions = [
            // Company Owner (alias: org_admin)
            'company_owner' => $allPermissionNames,
            'org_admin' => $allPermissionNames,
            'sales_manager' => [
                'leads.view', 'leads.create', 'leads.update', 'leads.delete',
                'customers.view', 'customers.create', 'customers.update',
                'projects.view', 'units.view',
                'bookings.view', 'bookings.create', 'bookings.update',
                'payments.view', 'payments.create',
                'visits.view', 'visits.create', 'visits.update',
                'follow_ups.view', 'follow_ups.create', 'follow_ups.update',
                'tasks.view', 'tasks.create', 'tasks.update',
                'documents.view', 'documents.create',
                'campaigns.view',
                'reports.view', 'dashboard.view',
            ],
            'sales_executive' => [
                'leads.view', 'leads.create', 'leads.update',
                'customers.view', 'customers.create',
                'projects.view', 'units.view',
                'bookings.view', 'bookings.create',
                'payments.view',
                'visits.view', 'visits.create', 'visits.update',
                'follow_ups.view', 'follow_ups.create', 'follow_ups.update',
                'tasks.view', 'tasks.create', 'tasks.update',
                'documents.view', 'documents.create',
                'dashboard.view',
            ],
            'marketing' => [
                'leads.view', 'leads.create', 'leads.update',
                'campaigns.view', 'campaigns.create', 'campaigns.update',
                'reports.view', 'dashboard.view',
            ],
            'marketing_manager' => [
                'leads.view', 'leads.create', 'leads.update',
                'campaigns.view', 'campaigns.create', 'campaigns.update',
                'reports.view', 'dashboard.view',
            ],
            'reception' => [
                'leads.view', 'leads.create', 'leads.update',
                'customers.view', 'customers.create',
                'visits.view', 'visits.create', 'visits.update',
                'follow_ups.view', 'follow_ups.create',
                'dashboard.view',
            ],
            'accountant' => [
                'customers.view',
                'bookings.view',
                'payments.view', 'payments.create', 'payments.update',
                'reports.view', 'dashboard.view',
                'documents.view', 'documents.create',
            ],
            'document_officer' => [
                'customers.view',
                'bookings.view',
                'documents.view', 'documents.create', 'documents.delete',
                'dashboard.view',
            ],
            'viewer' => $readOnly,
            'support_staff' => [
                'leads.view',
                'customers.view',
                'follow_ups.view', 'follow_ups.create', 'follow_ups.update',
                'tasks.view', 'tasks.create', 'tasks.update',
                'visits.view',
                'dashboard.view',
            ],
        ];

        foreach ($roleDefinitions as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }
    }

    public static function assignRoleForOrganization(int $organizationId, string $roleName): Role
    {
        app()[PermissionRegistrar::class]->setPermissionsTeamId($organizationId);

        return Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
            'organization_id' => $organizationId,
        ]);
    }
}
