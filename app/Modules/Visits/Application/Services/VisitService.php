<?php

declare(strict_types=1);

namespace App\Modules\Visits\Application\Services;

use App\Models\Visit;
use App\Models\VisitReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class VisitService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Visit::query()
            ->with(['lead', 'customer', 'project', 'assignee'])
            ->orderBy('scheduled_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Visit
    {
        return Visit::with(['lead', 'customer', 'project', 'assignee', 'reports'])->find($id);
    }

    public function schedule(array $data): Visit
    {
        return Visit::create($data);
    }

    public function checkIn(Visit $visit, ?array $location = null): Visit
    {
        $visit->update([
            'checked_in_at' => now(),
            'status' => 'in_progress',
            'latitude' => $location['latitude'] ?? $visit->latitude,
            'longitude' => $location['longitude'] ?? $visit->longitude,
        ]);

        return $visit->fresh();
    }

    public function complete(Visit $visit, array $reportData): Visit
    {
        return DB::transaction(function () use ($visit, $reportData): Visit {
            $visit->update(['status' => 'completed']);

            VisitReport::create([
                'visit_id' => $visit->id,
                'user_id' => Auth::id(),
                'outcome' => $reportData['outcome'],
                'summary' => $reportData['summary'] ?? null,
                'feedback' => $reportData['feedback'] ?? null,
            ]);

            return $visit->fresh(['reports']);
        });
    }

    public function cancel(Visit $visit, ?string $notes = null): Visit
    {
        $visit->update([
            'status' => 'cancelled',
            'notes' => $notes ?? $visit->notes,
        ]);

        return $visit->fresh();
    }
}
