<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UnitStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Models\Project;
use App\Models\Unit;
use App\Modules\Projects\Application\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Unit::query()->with(['project', 'building', 'floor']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $units = $query->paginate((int) $request->get('per_page', 50));

        return $this->success(
            UnitResource::collection($units)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'code' => ['required', 'string', 'max:50'],
            'type' => ['nullable', 'string'],
            'area_sqft' => ['nullable', 'numeric'],
            'price' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(UnitStatus::class)],
            'attributes' => ['nullable', 'array'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        $unit = $this->projectService->addUnit($project, $data);

        return $this->success(new UnitResource($unit), 'Unit created', 201);
    }

    public function show(Unit $unit): JsonResponse
    {
        return $this->success(new UnitResource($unit->load(['project', 'building', 'floor'])));
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50'],
            'type' => ['nullable', 'string'],
            'area_sqft' => ['nullable', 'numeric'],
            'price' => ['nullable', 'numeric'],
            'status' => ['sometimes', Rule::enum(UnitStatus::class)],
            'attributes' => ['nullable', 'array'],
        ]);

        $unit = $this->projectService->updateUnit($unit, $data);

        return $this->success(new UnitResource($unit), 'Unit updated');
    }
}
