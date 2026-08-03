<?php

declare(strict_types=1);

namespace App\Modules\Projects\Application\Services;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProjectService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Project::query()->withCount(['units', 'buildings'])->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Project
    {
        return Project::with(['buildings.floors', 'units', 'mediaAssets'])->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    public function delete(Project $project): bool
    {
        return (bool) $project->delete();
    }

    public function addBuilding(Project $project, array $data): Building
    {
        return $project->buildings()->create($data);
    }

    public function addFloor(Building $building, array $data): Floor
    {
        return $building->floors()->create($data);
    }

    public function addUnit(Project $project, array $data): Unit
    {
        return $project->units()->create($data);
    }

    public function updateUnit(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit->fresh();
    }

    public function listUnits(Project $project, array $filters = []): LengthAwarePaginator
    {
        $query = $project->units()->with(['building', 'floor']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 50);
    }
}
