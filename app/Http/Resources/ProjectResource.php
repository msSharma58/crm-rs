<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => $this->description,
            'status' => $this->status,
            'amenities' => $this->amenities,
            'units_count' => $this->whenCounted('units'),
            'buildings_count' => $this->whenCounted('buildings'),
            'buildings' => BuildingResource::collection($this->whenLoaded('buildings')),
            'units' => UnitResource::collection($this->whenLoaded('units')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
