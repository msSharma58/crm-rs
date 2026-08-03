<?php

declare(strict_types=1);

use App\Jobs\ProcessFacebookLeadWebhook;
use App\Jobs\ProcessWhatsAppWebhook;
use App\Models\IntegrationSetting;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\User;
use App\Modules\Integrations\Facebook\FacebookLeadSyncService;
use App\Modules\Integrations\WhatsApp\WhatsAppCloudService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->organization = Organization::create([
        'name' => 'Meta Homes',
        'slug' => 'meta-homes',
        'status' => 'active',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->organization->id);

    $this->user = User::create([
        'name' => 'Owner',
        'email' => 'owner@meta.test',
        'password' => Hash::make('password'),
        'organization_id' => $this->organization->id,
        'is_active' => true,
    ]);

    $fb = new IntegrationSetting([
        'organization_id' => $this->organization->id,
        'provider' => 'facebook',
        'is_active' => true,
        'settings' => ['page_id' => 'page_123'],
        'webhook_verify_token' => 'verify_fb_token',
    ]);
    $fb->setEncryptedCredentials(['page_access_token' => 'page_token_abc']);
    $fb->save();

    $wa = new IntegrationSetting([
        'organization_id' => $this->organization->id,
        'provider' => 'whatsapp',
        'is_active' => true,
        'settings' => [
            'phone_number_id' => 'pnid_99',
            'display_phone_number' => '+15550001111',
        ],
        'webhook_verify_token' => 'verify_wa_token',
    ]);
    $wa->setEncryptedCredentials([
        'access_token' => 'wa_token_abc',
        'phone_number_id' => 'pnid_99',
    ]);
    $wa->save();
});

it('verifies facebook webhook challenge', function (): void {
    $this->get('/api/v1/webhooks/facebook?hub.mode=subscribe&hub.verify_token=verify_fb_token&hub.challenge=12345')
        ->assertOk()
        ->assertSee('12345');
});

it('queues facebook lead webhook and creates a lead from graph data', function (): void {
    Queue::fake();

    $payload = [
        'object' => 'page',
        'entry' => [[
            'id' => 'page_123',
            'changes' => [[
                'field' => 'leadgen',
                'value' => [
                    'leadgen_id' => 'lead_555',
                    'form_id' => 'form_1',
                    'ad_id' => 'ad_1',
                    'created_time' => time(),
                ],
            ]],
        ]],
    ];

    $this->postJson('/api/v1/webhooks/facebook', $payload)->assertOk();
    Queue::assertPushed(ProcessFacebookLeadWebhook::class);

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'id' => 'lead_555',
            'created_time' => '2026-08-03T10:00:00+0000',
            'ad_id' => 'ad_1',
            'form_id' => 'form_1',
            'field_data' => [
                ['name' => 'full_name', 'values' => ['Ram Meta']],
                ['name' => 'phone_number', 'values' => ['+9779801112233']],
                ['name' => 'email', 'values' => ['ram.meta@example.com']],
            ],
        ], 200),
    ]);

    app(FacebookLeadSyncService::class)->handleWebhook($payload);

    $this->assertDatabaseHas('leads', [
        'organization_id' => $this->organization->id,
        'external_provider' => 'facebook',
        'external_id' => 'lead_555',
        'name' => 'Ram Meta',
        'email' => 'ram.meta@example.com',
    ]);
});

it('verifies whatsapp webhook challenge', function (): void {
    $this->get('/api/v1/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=verify_wa_token&hub.challenge=999')
        ->assertOk()
        ->assertSee('999');
});

it('creates a lead from inbound whatsapp message', function (): void {
    Queue::fake();

    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'waba',
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => [
                        'phone_number_id' => 'pnid_99',
                        'display_phone_number' => '15550001111',
                    ],
                    'contacts' => [[
                        'profile' => ['name' => 'Hari WA'],
                        'wa_id' => '9779802223344',
                    ]],
                    'messages' => [[
                        'from' => '9779802223344',
                        'id' => 'wamid.ABC123',
                        'timestamp' => (string) time(),
                        'type' => 'text',
                        'text' => ['body' => 'Hi, I want a 2BHK in Pokhara'],
                    ]],
                ],
            ]],
        ]],
    ];

    $this->postJson('/api/v1/webhooks/whatsapp', $payload)->assertOk();
    Queue::assertPushed(ProcessWhatsAppWebhook::class);

    app(WhatsAppCloudService::class)->handleInbound($payload);

    $this->assertDatabaseHas('leads', [
        'organization_id' => $this->organization->id,
        'external_provider' => 'whatsapp',
        'name' => 'Hari WA',
        'source' => 'whatsapp',
    ]);

    $this->assertDatabaseHas('integration_messages', [
        'organization_id' => $this->organization->id,
        'direction' => 'inbound',
        'external_id' => 'wamid.ABC123',
        'body' => 'Hi, I want a 2BHK in Pokhara',
    ]);
});

it('sends a whatsapp message via cloud api', function (): void {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.OUT1']],
        ], 200),
    ]);

    $lead = Lead::create([
        'organization_id' => $this->organization->id,
        'name' => 'Outbound Lead',
        'phone' => '+9779803334455',
        'source' => 'whatsapp',
        'status' => 'contacted',
        'priority' => 'medium',
    ]);

    $token = $this->user->createToken('test')->plainTextToken;

    $this->postJson("/api/v1/leads/{$lead->id}/whatsapp", [
        'message' => 'Thanks for your interest in Tilottama Homes!',
    ], [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ])->assertCreated()->assertJsonPath('data.external_id', 'wamid.OUT1');

    $this->assertDatabaseHas('integration_messages', [
        'lead_id' => $lead->id,
        'direction' => 'outbound',
        'body' => 'Thanks for your interest in Tilottama Homes!',
    ]);
});

it('saves integration settings for the organization', function (): void {
    $token = $this->user->createToken('test')->plainTextToken;

    $this->putJson('/api/v1/integrations/facebook', [
        'is_active' => true,
        'page_id' => 'page_999',
        'page_access_token' => 'new_token',
        'webhook_verify_token' => 'new_verify',
    ], [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ])->assertOk()->assertJsonPath('data.provider', 'facebook')
        ->assertJsonPath('data.has_page_access_token', true);

    $this->getJson('/api/v1/integrations', [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ])->assertOk();
});
