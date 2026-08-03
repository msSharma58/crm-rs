<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'location' => $this->location,
            'budget' => $this->budget,
            'preferred_property' => $this->preferred_property,
            'source' => $this->source,
            'status' => $this->status,
            'priority' => $this->priority,
            'ai_score' => $this->ai_score,
            'notes' => $this->notes,
            'last_contacted_at' => $this->last_contacted_at?->toIso8601String(),
            'converted_at' => $this->converted_at?->toIso8601String(),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'campaign' => new CampaignResource($this->whenLoaded('campaign')),
            'activities' => LeadActivityResource::collection($this->whenLoaded('activities')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
