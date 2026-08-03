<?php

declare(strict_types=1);

namespace App\Modules\FollowUps\Application\Services;

use App\Models\FollowUp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class FollowUpService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = FollowUp::query()
            ->with(['lead', 'customer', 'assignee'])
            ->orderBy('due_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?FollowUp
    {
        return FollowUp::with(['lead', 'customer', 'assignee'])->find($id);
    }

    public function create(array $data): FollowUp
    {
        return FollowUp::create($data);
    }

    public function update(FollowUp $followUp, array $data): FollowUp
    {
        $followUp->update($data);

        return $followUp->fresh();
    }

    public function complete(FollowUp $followUp, ?string $notes = null): FollowUp
    {
        $followUp->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $notes ?? $followUp->notes,
        ]);

        return $followUp->fresh();
    }

    public function cancel(FollowUp $followUp): FollowUp
    {
        $followUp->update(['status' => 'cancelled']);

        return $followUp->fresh();
    }

    public function overdue(): int
    {
        return FollowUp::query()
            ->where('status', 'pending')
            ->where('due_at', '<', now())
            ->count();
    }
}
