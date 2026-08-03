<?php

declare(strict_types=1);

namespace App\Modules\Leads\Infrastructure\Repositories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class LeadRepository
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Lead::query()
            ->with(['project', 'assignee', 'campaign'])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Lead
    {
        return Lead::query()
            ->with(['project', 'assignee', 'campaign', 'activities', 'notes'])
            ->find($id);
    }

    public function create(array $data): Lead
    {
        return Lead::create($data);
    }

    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);

        return $lead->fresh();
    }

    public function delete(Lead $lead): bool
    {
        return (bool) $lead->delete();
    }

    public function boardByStatus(): Collection
    {
        return Lead::query()
            ->with(['assignee', 'project'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy(fn (Lead $lead): string => $lead->status->value);
    }

    public function moveStatus(Lead $lead, LeadStatus $status): Lead
    {
        $lead->update(['status' => $status]);

        return $lead->fresh();
    }
}
