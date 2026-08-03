<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

it('prevents reading another organization customer', function (): void {
    $orgA = Organization::create(['name' => 'Org A', 'slug' => 'org-a', 'status' => 'active']);
    $orgB = Organization::create(['name' => 'Org B', 'slug' => 'org-b', 'status' => 'active']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($orgA->id);
    Permission::findOrCreate('customers.view', 'web');
    $role = Role::create([
        'name' => 'viewer',
        'guard_name' => 'web',
        'organization_id' => $orgA->id,
    ]);
    $role->givePermissionTo('customers.view');

    $userA = User::create([
        'name' => 'User A',
        'email' => 'a@test.test',
        'password' => Hash::make('password'),
        'organization_id' => $orgA->id,
        'is_active' => true,
    ]);
    $userA->assignRole($role);

    $customerB = Customer::withoutGlobalScopes()->create([
        'organization_id' => $orgB->id,
        'name' => 'Secret Customer',
        'phone' => '+977-9809999999',
    ]);

    $token = $userA->createToken('test')->plainTextToken;

    $response = $this->getJson("/api/v1/customers/{$customerB->id}", [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ]);

    // Scoped 404 or policy 403 both prove cross-tenant access is blocked.
    expect($response->status())->toBeIn([403, 404]);
});
