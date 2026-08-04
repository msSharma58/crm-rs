<?php

declare(strict_types=1);

namespace App\Modules\Integrations\WhatsApp;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\IntegrationMessage;
use App\Models\IntegrationSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WebhookEvent;
use App\Modules\Ai\Application\Services\AiLeadScoringService;
use App\Modules\Integrations\Contracts\MessagingChannel;
use App\Modules\Integrations\Facebook\MetaGraphClient;
use App\Core\Tenancy\OrganizationScope;
use App\Core\Tenancy\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class WhatsAppCloudService implements MessagingChannel
{
    public function __construct(
        private readonly AiLeadScoringService $aiLeadScoringService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function verifyWebhook(string $mode, string $verifyToken, string $challenge): ?string
    {
        if ($mode !== 'subscribe') {
            return null;
        }

        $matchesOrg = IntegrationSetting::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('provider', 'whatsapp')
            ->where('is_active', true)
            ->where('webhook_verify_token', $verifyToken)
            ->exists();

        $matchesDefault = hash_equals((string) config('integrations.meta.default_verify_token'), $verifyToken);

        return ($matchesOrg || $matchesDefault) ? $challenge : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleInbound(array $payload): void
    {
        $event = WebhookEvent::create([
            'provider' => 'whatsapp',
            'event_type' => 'messages',
            'event_id' => (string) Str::uuid(),
            'status' => 'received',
            'payload' => $payload,
        ]);

        try {
            $entries = Arr::get($payload, 'entry', []);
            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];
                    $phoneNumberId = (string) Arr::get($value, 'metadata.phone_number_id', '');
                    $setting = $this->resolveSettingForPhoneNumberId($phoneNumberId);

                    if ($setting === null) {
                        Log::warning('WhatsApp webhook: no active integration', ['phone_number_id' => $phoneNumberId]);
                        continue;
                    }

                    $event->update(['organization_id' => $setting->organization_id]);
                    $this->tenantContext->setOrganizationId((int) $setting->organization_id);

                    foreach ($value['messages'] ?? [] as $message) {
                        $this->processInboundMessage($setting, $value, $message);
                    }

                    foreach ($value['statuses'] ?? [] as $status) {
                        $this->processStatusUpdate($setting, $status);
                    }
                }
            }

            $event->update(['status' => 'processed']);
        } catch (\Throwable $e) {
            $event->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function sendText(Lead $lead, string $message): IntegrationMessage
    {
        $setting = IntegrationSetting::query()
            ->where('organization_id', $lead->organization_id)
            ->where('provider', 'whatsapp')
            ->where('is_active', true)
            ->firstOrFail();

        $phoneNumberId = (string) ($setting->settings['phone_number_id'] ?? $setting->credential('phone_number_id', ''));
        $token = (string) $setting->credential('access_token', '');
        $to = $this->digitsOnly((string) $lead->phone);

        if ($phoneNumberId === '' || $token === '' || $to === '') {
            throw new \RuntimeException('WhatsApp is not fully configured for this organization/lead.');
        }

        $client = new MetaGraphClient($token);
        $response = $client->post("{$phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $message],
        ]);

        $externalId = (string) Arr::get($response, 'messages.0.id', '');

        $record = IntegrationMessage::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'provider' => 'whatsapp',
            'direction' => 'outbound',
            'channel' => 'whatsapp',
            'external_id' => $externalId !== '' ? $externalId : null,
            'from_number' => (string) ($setting->settings['display_phone_number'] ?? $phoneNumberId),
            'to_number' => $to,
            'message_type' => 'text',
            'body' => $message,
            'raw_payload' => $response,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        LeadActivity::create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'type' => 'whatsapp',
            'title' => 'WhatsApp message sent',
            'description' => Str::limit($message, 240),
            'properties' => ['message_id' => $record->id, 'external_id' => $externalId],
        ]);

        $lead->update(['last_contacted_at' => now()]);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $message
     */
    private function processInboundMessage(IntegrationSetting $setting, array $value, array $message): void
    {
        DB::transaction(function () use ($setting, $value, $message): void {
            $from = $this->digitsOnly((string) ($message['from'] ?? ''));
            $externalId = (string) ($message['id'] ?? '');
            $type = (string) ($message['type'] ?? 'text');
            $body = match ($type) {
                'text' => (string) Arr::get($message, 'text.body', ''),
                'button' => (string) Arr::get($message, 'button.text', ''),
                'interactive' => (string) (Arr::get($message, 'interactive.button_reply.title')
                    ?? Arr::get($message, 'interactive.list_reply.title')
                    ?? ''),
                default => '['.$type.' message]',
            };

            if ($externalId !== '') {
                $exists = IntegrationMessage::query()
                    ->where('organization_id', $setting->organization_id)
                    ->where('external_id', $externalId)
                    ->exists();
                if ($exists) {
                    return;
                }
            }

            $profileName = (string) Arr::get($value, 'contacts.0.profile.name', 'WhatsApp Lead');
            $lead = $this->findOrCreateLead($setting, $from, $profileName, $body);

            IntegrationMessage::create([
                'organization_id' => $setting->organization_id,
                'lead_id' => $lead->id,
                'provider' => 'whatsapp',
                'direction' => 'inbound',
                'channel' => 'whatsapp',
                'external_id' => $externalId !== '' ? $externalId : null,
                'from_number' => $from,
                'to_number' => (string) Arr::get($value, 'metadata.display_phone_number'),
                'message_type' => $type,
                'body' => $body,
                'raw_payload' => $message,
                'status' => 'received',
            ]);

            LeadActivity::create([
                'organization_id' => $setting->organization_id,
                'lead_id' => $lead->id,
                'type' => 'whatsapp',
                'title' => 'WhatsApp message received',
                'description' => Str::limit($body, 240),
                'properties' => ['from' => $from, 'external_id' => $externalId],
            ]);

            if ($lead->status === LeadStatus::New) {
                $lead->update([
                    'status' => LeadStatus::Contacted,
                    'last_contacted_at' => now(),
                ]);
            } else {
                $lead->update(['last_contacted_at' => now()]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function processStatusUpdate(IntegrationSetting $setting, array $status): void
    {
        $externalId = (string) ($status['id'] ?? '');
        if ($externalId === '') {
            return;
        }

        IntegrationMessage::query()
            ->where('organization_id', $setting->organization_id)
            ->where('external_id', $externalId)
            ->update([
                'status' => (string) ($status['status'] ?? 'updated'),
            ]);
    }

    private function findOrCreateLead(IntegrationSetting $setting, string $phone, string $name, string $body): Lead
    {
        if ($phone !== '') {
            $existing = Lead::query()
                ->where('organization_id', $setting->organization_id)
                ->where(function ($q) use ($phone): void {
                    $q->where('phone', $phone)
                        ->orWhere('phone', 'like', "%{$phone}")
                        ->orWhere('external_id', $phone);
                })
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $attributes = [
            'organization_id' => $setting->organization_id,
            'name' => $name !== '' ? $name : 'WhatsApp Lead',
            'phone' => $phone !== '' ? $phone : 'unknown',
            'source' => LeadSource::WhatsApp->value,
            'external_provider' => 'whatsapp',
            'external_id' => $phone !== '' ? $phone : null,
            'status' => LeadStatus::New->value,
            'priority' => LeadPriority::High->value,
            'notes' => Str::limit($body, 500),
            'integration_meta' => ['channel' => 'whatsapp_cloud'],
        ];
        $attributes['ai_score'] = $this->aiLeadScoringService->score($attributes);

        return Lead::create($attributes);
    }

    private function resolveSettingForPhoneNumberId(string $phoneNumberId): ?IntegrationSetting
    {
        $settings = IntegrationSetting::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('provider', 'whatsapp')
            ->where('is_active', true)
            ->get();

        if ($phoneNumberId === '') {
            return $settings->first();
        }

        return $settings->first(function (IntegrationSetting $setting) use ($phoneNumberId): bool {
            return (string) ($setting->settings['phone_number_id'] ?? '') === $phoneNumberId
                || (string) $setting->credential('phone_number_id', '') === $phoneNumberId;
        }) ?? $settings->first();
    }

    private function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
