<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'method' => $this->method,
            'status' => $this->status,
            'receipt_no' => $this->receipt_no,
            'paid_at' => $this->paid_at?->toDateString(),
            'notes' => $this->notes,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'payment_schedule' => new PaymentScheduleResource($this->whenLoaded('paymentSchedule')),
            'recorder' => new UserResource($this->whenLoaded('recorder')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
