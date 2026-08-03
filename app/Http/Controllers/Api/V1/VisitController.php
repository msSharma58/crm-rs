<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisitResource;
use App\Models\Visit;
use App\Modules\Visits\Application\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function __construct(
        private readonly VisitService $visitService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $visits = $this->visitService->paginate(
            $request->only(['status', 'assigned_to']),
            (int) $request->get('per_page', 15)
        );

        return $this->success(
            VisitResource::collection($visits)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'location' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $visit = $this->visitService->schedule($data);

        return $this->success(new VisitResource($visit->load(['lead', 'customer', 'project', 'assignee'])), 'Visit scheduled', 201);
    }

    public function show(Visit $visit): JsonResponse
    {
        return $this->success(new VisitResource($this->visitService->find($visit->id)));
    }

    public function checkIn(Request $request, Visit $visit): JsonResponse
    {
        $location = $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $visit = $this->visitService->checkIn($visit, $location);

        return $this->success(new VisitResource($visit), 'Checked in');
    }

    public function complete(Request $request, Visit $visit): JsonResponse
    {
        $data = $request->validate([
            'outcome' => ['required', 'string'],
            'summary' => ['nullable', 'string'],
            'feedback' => ['nullable', 'array'],
        ]);

        $visit = $this->visitService->complete($visit, $data);

        return $this->success(new VisitResource($visit), 'Visit completed');
    }
}
