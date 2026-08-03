<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'title' => $this->title,
            'notes' => $this->notes,
            'due_at' => $this->due_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'status' => $this->status,
            'priority' => $this->priority,
            'lead' => new LeadResource($this->whenLoaded('lead')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
