<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Modules\Tasks\Application\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->paginate(
            $request->only(['status', 'assigned_to']),
            (int) $request->get('per_page', 15)
        );

        return $this->success(
            TaskResource::collection($tasks)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'taskable_type' => ['nullable', 'string'],
            'taskable_id' => ['nullable', 'integer'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ]);

        $task = $this->taskService->create($data);

        return $this->success(new TaskResource($task->load(['assignee', 'creator'])), 'Task created', 201);
    }

    public function show(Task $task): JsonResponse
    {
        return $this->success(new TaskResource($this->taskService->find($task->id)));
    }

    public function complete(Task $task): JsonResponse
    {
        $task = $this->taskService->complete($task);

        return $this->success(new TaskResource($task), 'Task completed');
    }

    public function addComment(Request $request, Task $task): JsonResponse
    {
        $request->validate(['body' => ['required', 'string']]);

        $comment = $this->taskService->addComment($task, $request->input('body'));

        return $this->success($comment, 'Comment added', 201);
    }
}
