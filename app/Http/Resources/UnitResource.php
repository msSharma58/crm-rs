<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'area_sqft' => $this->area_sqft,
            'price' => $this->price,
            'status' => $this->status,
            'attributes' => $this->attributes,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'building' => new BuildingResource($this->whenLoaded('building')),
            'floor' => new FloorResource($this->whenLoaded('floor')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
