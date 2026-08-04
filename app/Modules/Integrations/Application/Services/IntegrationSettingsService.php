<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Application\Services;

use App\Models\IntegrationSetting;
use App\Models\Organization;
use Illuminate\Support\Str;

final class IntegrationSettingsService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForOrganization(Organization $organization): array
    {
        return IntegrationSetting::query()
            ->where('organization_id', $organization->id)
            ->get()
            ->map(fn (IntegrationSetting $setting) => $this->toSafeArray($setting))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function upsert(Organization $organization, string $provider, array $data): array
    {
        $setting = IntegrationSetting::query()->firstOrNew([
            'organization_id' => $organization->id,
            'provider' => $provider,
        ]);

        $credentials = array_filter([
            'page_access_token' => $data['page_access_token'] ?? null,
            'access_token' => $data['access_token'] ?? null,
            'app_secret' => $data['app_secret'] ?? null,
            'phone_number_id' => $data['phone_number_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($credentials !== []) {
            $merged = array_merge($setting->exists ? $setting->getDecryptedCredentials() : [], $credentials);
            $setting->setEncryptedCredentials($merged);
        }

        $settings = array_filter([
            'page_id' => $data['page_id'] ?? null,
            'page_ids' => $data['page_ids'] ?? null,
            'phone_number_id' => $data['phone_number_id'] ?? null,
            'display_phone_number' => $data['display_phone_number'] ?? null,
            'waba_id' => $data['waba_id'] ?? null,
            'default_form_id' => $data['default_form_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $setting->settings = array_merge($setting->settings ?? [], $settings);
        $setting->is_active = (bool) ($data['is_active'] ?? $setting->is_active);
        $setting->webhook_verify_token = $data['webhook_verify_token']
            ?? $setting->webhook_verify_token
            ?? Str::random(32);
        $setting->organization_id = $organization->id;
        $setting->provider = $provider;
        $setting->save();

        return $this->toSafeArray($setting->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function toSafeArray(IntegrationSetting $setting): array
    {
        $credentials = $setting->getDecryptedCredentials();

        return [
            'id' => $setting->id,
            'provider' => $setting->provider,
            'is_active' => $setting->is_active,
            'settings' => $setting->settings ?? [],
            'webhook_verify_token' => $setting->webhook_verify_token,
            'has_page_access_token' => ! empty($credentials['page_access_token']),
            'has_access_token' => ! empty($credentials['access_token'] ?? $credentials['page_access_token'] ?? null),
            'last_synced_at' => $setting->last_synced_at?->toIso8601String(),
            'webhook_url' => url('/api/v1/webhooks/'.$setting->provider),
            'updated_at' => $setting->updated_at?->toIso8601String(),
        ];
    }
}
