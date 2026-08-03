<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'organization_id' => $this->organization_id,
            'is_super_admin' => $this->is_super_admin,
            'is_active' => $this->is_active,
            'avatar_path' => $this->avatar_path,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
