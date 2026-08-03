<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'provider' => $this->provider,
            'direction' => $this->direction,
            'channel' => $this->channel,
            'external_id' => $this->external_id,
            'from_number' => $this->from_number,
            'to_number' => $this->to_number,
            'message_type' => $this->message_type,
            'body' => $this->body,
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
