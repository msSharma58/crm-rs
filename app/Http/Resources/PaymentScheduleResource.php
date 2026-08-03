<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'amount' => $this->amount,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'sequence' => $this->sequence,
        ];
    }
}
