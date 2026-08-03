<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::create([
        'name' => 'Acme Realty',
        'slug' => 'acme-realty',
        'timezone' => 'Asia/Kathmandu',
        'currency' => 'NPR',
        'status' => 'active',
    ]);

    $this->user = User::create([
        'name' => 'Owner',
        'email' => 'owner@acme.test',
        'password' => Hash::make('password'),
        'organization_id' => $this->organization->id,
        'is_active' => true,
    ]);
});

it('logs in with valid credentials and returns a token', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@acme.test',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email']]]);
});

it('rejects invalid credentials', function (): void {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@acme.test',
        'password' => 'wrong-password',
    ])->assertStatus(422);
});

it('rejects inactive users', function (): void {
    $this->user->update(['is_active' => false]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'owner@acme.test',
        'password' => 'password',
    ])->assertForbidden();
});
