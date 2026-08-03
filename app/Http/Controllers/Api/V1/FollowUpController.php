<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FollowUpResource;
use App\Models\FollowUp;
use App\Modules\FollowUps\Application\Services\FollowUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function __construct(
        private readonly FollowUpService $followUpService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $followUps = $this->followUpService->paginate(
            $request->only(['status', 'assigned_to']),
            (int) $request->get('per_page', 15)
        );

        return $this->success(
            FollowUpResource::collection($followUps)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'channel' => ['nullable', 'string'],
            'title' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'due_at' => ['required', 'date'],
            'priority' => ['nullable', 'string'],
        ]);

        $followUp = $this->followUpService->create($data);

        return $this->success(new FollowUpResource($followUp->load(['lead', 'customer', 'assignee'])), 'Follow-up created', 201);
    }

    public function show(FollowUp $followUp): JsonResponse
    {
        return $this->success(new FollowUpResource($this->followUpService->find($followUp->id)));
    }

    public function complete(Request $request, FollowUp $followUp): JsonResponse
    {
        $followUp = $this->followUpService->complete($followUp, $request->input('notes'));

        return $this->success(new FollowUpResource($followUp), 'Follow-up completed');
    }
}
