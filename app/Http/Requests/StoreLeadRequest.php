<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'preferred_property' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'source' => ['nullable', Rule::enum(LeadSource::class)],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'status' => ['nullable', Rule::enum(LeadStatus::class)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', Rule::enum(LeadPriority::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
