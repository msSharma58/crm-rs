<?php

declare(strict_types=1);

use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::create([
        'name' => 'Polish Co',
        'slug' => 'polish-co',
        'status' => 'active',
        'currency' => 'NPR',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    foreach ([
        'leads.view', 'leads.create', 'leads.update', 'leads.delete',
        'customers.view', 'customers.create', 'customers.update',
        'campaigns.view', 'campaigns.create',
        'documents.view', 'documents.create',
        'payments.view', 'dashboard.view', 'reports.view',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role = Role::create([
        'name' => 'org_admin',
        'guard_name' => 'web',
        'organization_id' => $this->organization->id,
    ]);
    $role->syncPermissions(Permission::all());

    $this->user = User::create([
        'name' => 'Owner',
        'email' => 'owner@polish.test',
        'password' => Hash::make('password'),
        'organization_id' => $this->organization->id,
        'is_active' => true,
    ]);
    $this->user->assignRole($role);

    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = [
        'Authorization' => "Bearer {$this->token}",
        'Accept' => 'application/json',
    ];
});

it('returns all pipeline stages on the board including empty ones', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    Lead::create([
        'organization_id' => $this->organization->id,
        'name' => 'Only New',
        'phone' => '+977-9800111222',
        'source' => 'manual',
        'status' => LeadStatus::New->value,
        'priority' => 'medium',
    ]);

    $response = $this->getJson('/api/v1/leads/board', $this->headers);
    $response->assertOk();

    $statuses = collect($response->json('data'))->pluck('status')->all();
    expect($statuses)->toContain('new', 'cancelled', 'sold');

    $newColumn = collect($response->json('data'))->firstWhere('status', 'new');
    expect($newColumn['leads'])->toBeArray()->not->toBeEmpty();
    expect($newColumn['leads'][0])->toHaveKey('name');
});

it('supports customer family member CRUD', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    $customer = Customer::create([
        'organization_id' => $this->organization->id,
        'name' => 'Parent Customer',
        'phone' => '+977-9800333444',
    ]);

    $create = $this->postJson("/api/v1/customers/{$customer->id}/family", [
        'name' => 'Child One',
        'relation' => 'son',
        'phone' => '+977-9800555666',
    ], $this->headers);

    $create->assertCreated()->assertJsonPath('data.name', 'Child One');
    $memberId = $create->json('data.id');

    $this->putJson("/api/v1/customers/{$customer->id}/family/{$memberId}", [
        'relation' => 'daughter',
    ], $this->headers)->assertOk()->assertJsonPath('data.relation', 'daughter');

    $this->deleteJson("/api/v1/customers/{$customer->id}/family/{$memberId}", [], $this->headers)
        ->assertOk();
});

it('returns campaign ROI metrics', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    $campaign = Campaign::create([
        'organization_id' => $this->organization->id,
        'name' => 'ROI Test',
        'channel' => 'facebook',
        'budget' => 100000,
        'spend' => 40000,
        'status' => 'active',
    ]);

    Lead::create([
        'organization_id' => $this->organization->id,
        'campaign_id' => $campaign->id,
        'name' => 'Camp Lead',
        'phone' => '+977-9800777888',
        'source' => 'facebook',
        'status' => LeadStatus::Sold->value,
        'priority' => 'high',
    ]);

    $this->getJson("/api/v1/campaigns/{$campaign->id}/roi", $this->headers)
        ->assertOk()
        ->assertJsonPath('data.leads_count', 1)
        ->assertJsonPath('data.converted_count', 1)
        ->assertJsonStructure(['data' => ['cost_per_lead', 'conversion_rate', 'roi']]);
});

it('exports leads report as csv', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    Lead::create([
        'organization_id' => $this->organization->id,
        'name' => 'CSV Lead',
        'phone' => '+977-9800999000',
        'source' => 'website',
        'status' => LeadStatus::New->value,
        'priority' => 'low',
    ]);

    $response = $this->get('/api/v1/reports/leads?format=csv', $this->headers);
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

it('uploads a document file', function (): void {
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);
    Storage::fake('local');

    $customer = Customer::create([
        'organization_id' => $this->organization->id,
        'name' => 'Doc Customer',
        'phone' => '+977-9800123123',
    ]);

    $file = UploadedFile::fake()->create('citizenship.pdf', 120, 'application/pdf');

    $this->post('/api/v1/documents', [
        'title' => 'Citizenship',
        'type' => 'citizenship',
        'documentable_type' => Customer::class,
        'documentable_id' => $customer->id,
        'file' => $file,
    ], $this->headers)->assertCreated()->assertJsonPath('data.title', 'Citizenship');
});
