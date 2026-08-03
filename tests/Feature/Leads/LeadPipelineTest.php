<?php

declare(strict_types=1);

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::create([
        'name' => 'Tilottama Test',
        'slug' => 'tilottama-test',
        'timezone' => 'Asia/Kathmandu',
        'currency' => 'NPR',
        'status' => 'active',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    foreach (['leads.view', 'leads.create', 'leads.update', 'leads.delete', 'dashboard.view'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role = Role::create([
        'name' => 'sales_manager',
        'guard_name' => 'web',
        'organization_id' => $this->organization->id,
    ]);
    $role->syncPermissions(['leads.view', 'leads.create', 'leads.update', 'leads.delete', 'dashboard.view']);

    $this->user = User::create([
        'name' => 'Manager',
        'email' => 'manager@test.test',
        'password' => Hash::make('password'),
        'organization_id' => $this->organization->id,
        'is_active' => true,
    ]);
    $this->user->assignRole($role);

    $this->otherOrg = Organization::create([
        'name' => 'Other Co',
        'slug' => 'other-co',
        'status' => 'active',
    ]);
});

function authHeaders(User $user): array
{
    $token = $user->createToken('test')->plainTextToken;

    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
}

it('creates a lead in the authenticated organization', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    $response = $this->postJson('/api/v1/leads', [
        'name' => 'Hari Bahadur',
        'phone' => '+977-9801112233',
        'email' => 'hari@example.com',
        'source' => 'walk_in',
        'priority' => 'high',
    ], authHeaders($this->user));

    $response->assertCreated()->assertJsonPath('data.name', 'Hari Bahadur');

    $this->assertDatabaseHas('leads', [
        'name' => 'Hari Bahadur',
        'organization_id' => $this->organization->id,
        'status' => LeadStatus::New->value,
    ]);
});

it('moves a lead through the pipeline', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    $lead = Lead::create([
        'organization_id' => $this->organization->id,
        'name' => 'Pipeline Lead',
        'phone' => '+977-9800001111',
        'source' => 'manual',
        'status' => LeadStatus::New->value,
        'priority' => 'medium',
        'assigned_to' => $this->user->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->postJson("/api/v1/leads/{$lead->id}/status", [
        'status' => LeadStatus::Contacted->value,
    ], authHeaders($this->user));

    $response->assertOk()->assertJsonPath('data.status', LeadStatus::Contacted->value);

    expect($lead->fresh()->status)->toBe(LeadStatus::Contacted);
});

it('isolates leads between tenants', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    Lead::create([
        'organization_id' => $this->organization->id,
        'name' => 'Mine',
        'phone' => '+977-9800002222',
        'source' => 'manual',
        'status' => LeadStatus::New->value,
        'priority' => 'medium',
    ]);

    Lead::withoutGlobalScopes()->create([
        'organization_id' => $this->otherOrg->id,
        'name' => 'Theirs',
        'phone' => '+977-9800003333',
        'source' => 'manual',
        'status' => LeadStatus::New->value,
        'priority' => 'medium',
    ]);

    $response = $this->getJson('/api/v1/leads', authHeaders($this->user));
    $response->assertOk();

    $names = collect($response->json('data.data') ?? $response->json('data'))
        ->pluck('name')
        ->all();

    expect($names)->toContain('Mine')->not->toContain('Theirs');
});
