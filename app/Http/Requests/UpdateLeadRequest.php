<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'preferred_property' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'source' => ['sometimes', Rule::enum(LeadSource::class)],
            'campaign_id' => ['nullable', 'integer', 'exists:campaigns,id'],
            'status' => ['sometimes', Rule::enum(LeadStatus::class)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['sometimes', Rule::enum(LeadPriority::class)],
            'notes' => ['nullable', 'string'],
            'ai_score' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
