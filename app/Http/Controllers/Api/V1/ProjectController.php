<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Modules\Projects\Application\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->paginate(
            $request->only(['status', 'search']),
            (int) $request->get('per_page', 15)
        );

        return $this->success(
            ProjectResource::collection($projects)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'amenities' => ['nullable', 'array'],
        ]);

        $project = $this->projectService->create($data);

        return $this->success(new ProjectResource($project), 'Project created', 201);
    }

    public function show(Project $project): JsonResponse
    {
        return $this->success(new ProjectResource($this->projectService->find($project->id)));
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'amenities' => ['nullable', 'array'],
        ]);

        $project = $this->projectService->update($project, $data);

        return $this->success(new ProjectResource($project), 'Project updated');
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->projectService->delete($project);

        return $this->success(null, 'Project deleted');
    }
}
