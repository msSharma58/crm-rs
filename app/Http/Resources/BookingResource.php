<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'booking_amount' => $this->booking_amount,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'booked_at' => $this->booked_at?->toDateString(),
            'negotiation_notes' => $this->negotiation_notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'unit' => new UnitResource($this->whenLoaded('unit')),
            'project' => new ProjectResource($this->whenLoaded('project')),
            'sales_executive' => new UserResource($this->whenLoaded('salesExecutive')),
            'payment_schedules' => PaymentScheduleResource::collection($this->whenLoaded('paymentSchedules')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
