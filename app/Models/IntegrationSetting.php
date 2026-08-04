<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Tenancy\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'provider',
        'is_active',
        'credentials',
        'settings',
        'webhook_verify_token',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDecryptedCredentials(): array
    {
        if ($this->credentials === null || $this->credentials === '') {
            return [];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode(Crypt::decryptString($this->credentials), true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function setEncryptedCredentials(array $credentials): void
    {
        $this->credentials = Crypt::encryptString(json_encode($credentials, JSON_THROW_ON_ERROR));
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return $this->getDecryptedCredentials()[$key] ?? $default;
    }
}
