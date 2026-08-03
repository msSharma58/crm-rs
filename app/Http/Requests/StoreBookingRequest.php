<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'sales_executive_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'booking_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'negotiation_notes' => ['nullable', 'string'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.label' => ['required_with:schedules', 'string'],
            'schedules.*.amount' => ['required_with:schedules', 'numeric', 'min:0'],
            'schedules.*.due_date' => ['required_with:schedules', 'date'],
            'schedules.*.sequence' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
