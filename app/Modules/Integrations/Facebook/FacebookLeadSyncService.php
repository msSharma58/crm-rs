<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Facebook;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Campaign;
use App\Models\IntegrationSetting;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Organization;
use App\Models\WebhookEvent;
use App\Modules\Ai\Application\Services\AiLeadScoringService;
use App\Modules\Integrations\Contracts\LeadIngestor;
use App\Core\Tenancy\OrganizationScope;
use App\Core\Tenancy\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FacebookLeadSyncService implements LeadIngestor
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
            ->where('provider', 'facebook')
            ->where('is_active', true)
            ->where('webhook_verify_token', $verifyToken)
            ->exists();

        $matchesDefault = hash_equals((string) config('integrations.meta.default_verify_token'), $verifyToken);

        return ($matchesOrg || $matchesDefault) ? $challenge : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void
    {
        $event = WebhookEvent::create([
            'provider' => 'facebook',
            'event_type' => 'leadgen',
            'event_id' => (string) Str::uuid(),
            'status' => 'received',
            'payload' => $payload,
        ]);

        try {
            $entries = Arr::get($payload, 'entry', []);
            foreach ($entries as $entry) {
                $pageId = (string) ($entry['id'] ?? '');
                $changes = $entry['changes'] ?? [];
                foreach ($changes as $change) {
                    if (($change['field'] ?? null) !== 'leadgen') {
                        continue;
                    }

                    $value = $change['value'] ?? [];
                    $leadgenId = (string) ($value['leadgen_id'] ?? '');
                    if ($leadgenId === '') {
                        continue;
                    }

                    $setting = $this->resolveSettingForPage($pageId);
                    if ($setting === null) {
                        Log::warning('Facebook lead webhook: no active integration for page', ['page_id' => $pageId]);
                        continue;
                    }

                    $event->update(['organization_id' => $setting->organization_id]);
                    $this->tenantContext->setOrganizationId((int) $setting->organization_id);

                    $this->ingestLeadgen($setting, $leadgenId, $value);
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

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(array $payload): ?Lead
    {
        $organizationId = (int) ($payload['organization_id'] ?? 0);
        $leadgenId = (string) ($payload['leadgen_id'] ?? '');
        $setting = IntegrationSetting::query()
            ->where('organization_id', $organizationId)
            ->where('provider', 'facebook')
            ->where('is_active', true)
            ->first();

        if ($setting === null || $leadgenId === '') {
            return null;
        }

        $this->tenantContext->setOrganizationId($organizationId);

        return $this->ingestLeadgen($setting, $leadgenId, $payload);
    }

    /**
     * Pull recent leads for a configured form (manual/backfill sync).
     *
     * @return array{imported:int, skipped:int, errors:list<string>}
     */
    public function syncForm(Organization $organization, string $formId): array
    {
        $setting = IntegrationSetting::query()
            ->where('organization_id', $organization->id)
            ->where('provider', 'facebook')
            ->where('is_active', true)
            ->firstOrFail();

        $this->tenantContext->setOrganizationId((int) $organization->id);
        $client = $this->graphClient($setting);

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $after = null;

        do {
            $page = $client->fetchFormLeads($formId, $after);
            foreach ($page['data'] ?? [] as $leadPayload) {
                try {
                    $leadgenId = (string) ($leadPayload['id'] ?? '');
                    if ($leadgenId === '') {
                        continue;
                    }

                    $existing = Lead::query()
                        ->where('external_provider', 'facebook')
                        ->where('external_id', $leadgenId)
                        ->exists();

                    if ($existing) {
                        $skipped++;
                        continue;
                    }

                    $this->upsertFromGraphLead($setting, $leadPayload);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }

            $after = $page['paging']['cursors']['after'] ?? null;
            $hasNext = isset($page['paging']['next']);
        } while ($hasNext && $after);

        $setting->update(['last_synced_at' => now()]);

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function ingestLeadgen(IntegrationSetting $setting, string $leadgenId, array $value): Lead
    {
        $existing = Lead::query()
            ->where('organization_id', $setting->organization_id)
            ->where('external_provider', 'facebook')
            ->where('external_id', $leadgenId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $graphLead = $this->graphClient($setting)->fetchLead($leadgenId);

        return $this->upsertFromGraphLead($setting, array_merge($value, $graphLead));
    }

    /**
     * @param  array<string, mixed>  $graphLead
     */
    private function upsertFromGraphLead(IntegrationSetting $setting, array $graphLead): Lead
    {
        return DB::transaction(function () use ($setting, $graphLead): Lead {
            $fields = $this->normalizeFieldData($graphLead['field_data'] ?? []);
            $name = $fields['full_name'] ?? $fields['name'] ?? trim(($fields['first_name'] ?? '').' '.($fields['last_name'] ?? ''));
            $phone = $this->normalizePhone($fields['phone_number'] ?? $fields['phone'] ?? null);
            $email = $fields['email'] ?? null;

            if (($name === '' || $name === null) && $phone === null && $email === null) {
                $name = 'Facebook Lead';
            }

            $leadgenId = (string) ($graphLead['id'] ?? $graphLead['leadgen_id'] ?? '');
            $campaign = $this->resolveCampaign($setting, $graphLead, $fields);

            $attributes = [
                'organization_id' => $setting->organization_id,
                'name' => $name !== '' && $name !== null ? $name : 'Facebook Lead',
                'phone' => $phone ?? 'unknown',
                'email' => $email,
                'location' => $fields['city'] ?? $fields['location'] ?? null,
                'budget' => isset($fields['budget']) ? (float) $fields['budget'] : null,
                'preferred_property' => $fields['preferred_property'] ?? $fields['property_type'] ?? null,
                'source' => LeadSource::Facebook->value,
                'external_provider' => 'facebook',
                'external_id' => $leadgenId,
                'campaign_id' => $campaign?->id,
                'status' => LeadStatus::New->value,
                'priority' => LeadPriority::High->value,
                'notes' => 'Imported from Facebook Lead Ads',
                'integration_meta' => [
                    'ad_id' => $graphLead['ad_id'] ?? null,
                    'form_id' => $graphLead['form_id'] ?? null,
                    'campaign_id' => $graphLead['campaign_id'] ?? null,
                    'adset_id' => $graphLead['adset_id'] ?? $graphLead['adgroup_id'] ?? null,
                    'field_data' => $fields,
                    'created_time' => $graphLead['created_time'] ?? null,
                ],
            ];

            $attributes['ai_score'] = $this->aiLeadScoringService->score($attributes);

            $lead = Lead::query()->updateOrCreate(
                [
                    'organization_id' => $setting->organization_id,
                    'external_provider' => 'facebook',
                    'external_id' => $leadgenId,
                ],
                $attributes,
            );

            LeadActivity::create([
                'organization_id' => $setting->organization_id,
                'lead_id' => $lead->id,
                'type' => 'system',
                'title' => 'Facebook lead synced',
                'description' => 'Lead received from Facebook Lead Ads webhook/sync',
                'properties' => [
                    'leadgen_id' => $leadgenId,
                    'form_id' => $graphLead['form_id'] ?? null,
                    'ad_id' => $graphLead['ad_id'] ?? null,
                ],
            ]);

            return $lead;
        });
    }

    private function resolveSettingForPage(string $pageId): ?IntegrationSetting
    {
        $query = IntegrationSetting::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('provider', 'facebook')
            ->where('is_active', true);

        if ($pageId === '') {
            return $query->first();
        }

        return $query
            ->get()
            ->first(function (IntegrationSetting $setting) use ($pageId): bool {
                $settings = $setting->settings ?? [];

                return (string) ($settings['page_id'] ?? '') === $pageId
                    || in_array($pageId, $settings['page_ids'] ?? [], true);
            });
    }

    /**
     * @param  array<int, array<string, mixed>>  $fieldData
     * @return array<string, string>
     */
    private function normalizeFieldData(array $fieldData): array
    {
        $normalized = [];
        foreach ($fieldData as $field) {
            $name = Str::snake((string) ($field['name'] ?? ''));
            $values = $field['values'] ?? [];
            $value = is_array($values) ? (string) ($values[0] ?? '') : (string) $values;
            if ($name !== '') {
                $normalized[$name] = trim($value);
            }
        }

        return $normalized;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $phone);

        return $digits !== '' ? $digits : null;
    }

    /**
     * @param  array<string, mixed>  $graphLead
     * @param  array<string, string>  $fields
     */
    private function resolveCampaign(IntegrationSetting $setting, array $graphLead, array $fields): ?Campaign
    {
        $externalCampaignId = (string) ($graphLead['campaign_id'] ?? '');
        $nameHint = $fields['campaign'] ?? null;

        if ($externalCampaignId !== '') {
            $campaign = Campaign::query()
                ->where('organization_id', $setting->organization_id)
                ->where('channel', 'facebook')
                ->where('name', 'like', "%{$externalCampaignId}%")
                ->first();

            if ($campaign !== null) {
                return $campaign;
            }
        }

        if ($nameHint) {
            return Campaign::query()
                ->where('organization_id', $setting->organization_id)
                ->where('channel', 'facebook')
                ->where('name', 'like', "%{$nameHint}%")
                ->first();
        }

        return Campaign::query()
            ->where('organization_id', $setting->organization_id)
            ->where('channel', 'facebook')
            ->where('status', 'active')
            ->latest()
            ->first();
    }

    private function graphClient(IntegrationSetting $setting): MetaGraphClient
    {
        $token = (string) $setting->credential('page_access_token', '');
        if ($token === '') {
            throw new \RuntimeException('Facebook page access token is not configured.');
        }

        return new MetaGraphClient($token);
    }
}
