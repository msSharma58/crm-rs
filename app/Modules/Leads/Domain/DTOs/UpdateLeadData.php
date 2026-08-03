<?php

declare(strict_types=1);

namespace App\Modules\Leads\Domain\DTOs;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;

final readonly class UpdateLeadData
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $location = null,
        public ?float $budget = null,
        public ?string $preferredProperty = null,
        public ?int $projectId = null,
        public ?LeadSource $source = null,
        public ?int $campaignId = null,
        public ?LeadStatus $status = null,
        public ?int $assignedTo = null,
        public ?LeadPriority $priority = null,
        public ?string $notes = null,
        public ?int $aiScore = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            location: $data['location'] ?? null,
            budget: isset($data['budget']) ? (float) $data['budget'] : null,
            preferredProperty: $data['preferred_property'] ?? $data['preferredProperty'] ?? null,
            projectId: isset($data['project_id']) ? (int) $data['project_id'] : null,
            source: isset($data['source'])
                ? ($data['source'] instanceof LeadSource ? $data['source'] : LeadSource::from($data['source']))
                : null,
            campaignId: array_key_exists('campaign_id', $data) ? ($data['campaign_id'] !== null ? (int) $data['campaign_id'] : null) : null,
            status: isset($data['status'])
                ? ($data['status'] instanceof LeadStatus ? $data['status'] : LeadStatus::from($data['status']))
                : null,
            assignedTo: array_key_exists('assigned_to', $data) ? ($data['assigned_to'] !== null ? (int) $data['assigned_to'] : null) : null,
            priority: isset($data['priority'])
                ? ($data['priority'] instanceof LeadPriority ? $data['priority'] : LeadPriority::from($data['priority']))
                : null,
            notes: $data['notes'] ?? null,
            aiScore: isset($data['ai_score']) ? (int) $data['ai_score'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }
        if ($this->email !== null) {
            $data['email'] = $this->email;
        }
        if ($this->location !== null) {
            $data['location'] = $this->location;
        }
        if ($this->budget !== null) {
            $data['budget'] = $this->budget;
        }
        if ($this->preferredProperty !== null) {
            $data['preferred_property'] = $this->preferredProperty;
        }
        if ($this->projectId !== null) {
            $data['project_id'] = $this->projectId;
        }
        if ($this->source !== null) {
            $data['source'] = $this->source->value;
        }
        if ($this->campaignId !== null) {
            $data['campaign_id'] = $this->campaignId;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }
        if ($this->assignedTo !== null) {
            $data['assigned_to'] = $this->assignedTo;
        }
        if ($this->priority !== null) {
            $data['priority'] = $this->priority->value;
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }
        if ($this->aiScore !== null) {
            $data['ai_score'] = $this->aiScore;
        }

        return $data;
    }
}
