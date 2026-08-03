<?php

declare(strict_types=1);

namespace App\Modules\Leads\Domain\DTOs;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;

final readonly class CreateLeadData
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email = null,
        public ?string $location = null,
        public ?float $budget = null,
        public ?string $preferredProperty = null,
        public ?int $projectId = null,
        public LeadSource $source = LeadSource::Manual,
        public ?int $campaignId = null,
        public LeadStatus $status = LeadStatus::New,
        public ?int $assignedTo = null,
        public LeadPriority $priority = LeadPriority::Medium,
        public ?string $notes = null,
        public ?int $createdBy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            phone: $data['phone'],
            email: $data['email'] ?? null,
            location: $data['location'] ?? null,
            budget: isset($data['budget']) ? (float) $data['budget'] : null,
            preferredProperty: $data['preferred_property'] ?? $data['preferredProperty'] ?? null,
            projectId: isset($data['project_id']) ? (int) $data['project_id'] : null,
            source: isset($data['source'])
                ? ($data['source'] instanceof LeadSource ? $data['source'] : LeadSource::from($data['source']))
                : LeadSource::Manual,
            campaignId: isset($data['campaign_id']) ? (int) $data['campaign_id'] : null,
            status: isset($data['status'])
                ? ($data['status'] instanceof LeadStatus ? $data['status'] : LeadStatus::from($data['status']))
                : LeadStatus::New,
            assignedTo: isset($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            priority: isset($data['priority'])
                ? ($data['priority'] instanceof LeadPriority ? $data['priority'] : LeadPriority::from($data['priority']))
                : LeadPriority::Medium,
            notes: $data['notes'] ?? null,
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'location' => $this->location,
            'budget' => $this->budget,
            'preferred_property' => $this->preferredProperty,
            'project_id' => $this->projectId,
            'source' => $this->source->value,
            'campaign_id' => $this->campaignId,
            'status' => $this->status->value,
            'assigned_to' => $this->assignedTo,
            'priority' => $this->priority->value,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
        ], fn (mixed $value): bool => $value !== null);
    }
}
