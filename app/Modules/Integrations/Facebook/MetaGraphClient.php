<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Facebook;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MetaGraphClient
{
    public function __construct(
        private readonly string $accessToken,
        private readonly ?string $graphVersion = null,
        private readonly ?string $baseUrl = null,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $response = $this->client()->get($this->url($path), $query);

        if ($response->failed()) {
            throw new RuntimeException('Meta Graph GET failed: '.$response->body());
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        $response = $this->client()->post($this->url($path), $payload);

        if ($response->failed()) {
            throw new RuntimeException('Meta Graph POST failed: '.$response->body());
        }

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchLead(string $leadgenId): array
    {
        return $this->get($leadgenId, [
            'fields' => 'created_time,ad_id,form_id,field_data,campaign_id,adset_id',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchFormLeads(string $formId, ?string $after = null): array
    {
        $query = [
            'fields' => 'created_time,id,ad_id,form_id,field_data',
            'limit' => 50,
        ];

        if ($after !== null) {
            $query['after'] = $after;
        }

        return $this->get("{$formId}/leads", $query);
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(20)
            ->withToken($this->accessToken);
    }

    private function url(string $path): string
    {
        $base = rtrim($this->baseUrl ?? (string) config('integrations.meta.graph_base'), '/');
        $version = $this->graphVersion ?? (string) config('integrations.meta.graph_version');
        $path = ltrim($path, '/');

        return "{$base}/{$version}/{$path}";
    }
}
