<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Tenancy\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationMessage extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'lead_id',
        'customer_id',
        'provider',
        'direction',
        'channel',
        'external_id',
        'from_number',
        'to_number',
        'message_type',
        'body',
        'raw_payload',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
