<?php

declare(strict_types=1);

namespace App\Modules\Leads\Application\Services;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Modules\Automation\Application\Services\AutomationService;
use App\Modules\Ai\Application\Services\AiLeadScoringService;
use App\Modules\Leads\Domain\DTOs\CreateLeadData;
use App\Modules\Leads\Domain\DTOs\UpdateLeadData;
use App\Modules\Leads\Infrastructure\Repositories\LeadRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class LeadService
{
    public function __construct(
        private readonly LeadRepository $repository,
        private readonly AiLeadScoringService $aiScoringService,
        private readonly AutomationService $automationService,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function find(int $id): ?Lead
    {
        return $this->repository->find($id);
    }

    public function create(CreateLeadData $data): Lead
    {
        return DB::transaction(function () use ($data): Lead {
            $attributes = $data->toArray();
            $attributes['created_by'] ??= Auth::id();
            $attributes['ai_score'] = $this->aiScoringService->score($attributes);

            $lead = $this->repository->create($attributes);

            $this->automationService->handleLeadCreated($lead);

            return $lead->load(['project', 'assignee', 'campaign']);
        });
    }

    public function update(Lead $lead, UpdateLeadData $data): Lead
    {
        return $this->repository->update($lead, $data->toArray());
    }

    public function changeStatus(Lead $lead, LeadStatus $status): Lead
    {
        return DB::transaction(function () use ($lead, $status): Lead {
            $oldStatus = $lead->status;

            $lead = $this->repository->moveStatus($lead, $status);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => Auth::id(),
                'type' => 'status_change',
                'title' => 'Status changed',
                'description' => "Status changed from {$oldStatus->value} to {$status->value}",
                'properties' => [
                    'from' => $oldStatus->value,
                    'to' => $status->value,
                ],
            ]);

            return $lead;
        });
    }

    public function assign(Lead $lead, ?int $userId): Lead
    {
        return DB::transaction(function () use ($lead, $userId): Lead {
            $lead = $this->repository->update($lead, ['assigned_to' => $userId]);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => Auth::id(),
                'type' => 'assign',
                'title' => 'Lead assigned',
                'description' => $userId ? "Assigned to user #{$userId}" : 'Unassigned',
                'properties' => ['assigned_to' => $userId],
            ]);

            return $lead;
        });
    }

    public function convertToCustomer(Lead $lead): Customer
    {
        return DB::transaction(function () use ($lead): Customer {
            $customer = Customer::create([
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'location' => $lead->location,
                'assigned_to' => $lead->assigned_to,
            ]);

            $this->repository->update($lead, [
                'converted_at' => now(),
                'status' => LeadStatus::Booking->value,
            ]);

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => Auth::id(),
                'type' => 'system',
                'title' => 'Converted to customer',
                'description' => "Customer #{$customer->id} created",
                'properties' => ['customer_id' => $customer->id],
            ]);

            return $customer;
        });
    }

    public function boardByStatus(): Collection
    {
        return $this->repository->boardByStatus();
    }

    public function delete(Lead $lead): bool
    {
        return $this->repository->delete($lead);
    }

    public function importCsv(string $path): array
    {
        $imported = 0;
        $errors = [];

        if (! file_exists($path) || ($handle = fopen($path, 'r')) === false) {
            return ['imported' => 0, 'errors' => ['Unable to open CSV file']];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return ['imported' => 0, 'errors' => ['CSV file is empty']];
        }

        $row = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            $record = array_combine($headers, $data);

            if ($record === false || empty($record['name']) || empty($record['phone'])) {
                $errors[] = "Row {$row}: name and phone are required";

                continue;
            }

            try {
                $this->create(CreateLeadData::fromArray([
                    ...$record,
                    'source' => LeadSource::ImportCsv->value,
                ]));
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$row}: {$e->getMessage()}";
            }
        }

        fclose($handle);

        return ['imported' => $imported, 'errors' => $errors];
    }
}
