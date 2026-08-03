<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'location' => $this->location,
            'occupation' => $this->occupation,
            'meta' => $this->meta,
            'lead_id' => $this->lead_id,
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'family_members' => CustomerFamilyMemberResource::collection($this->whenLoaded('familyMembers')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
