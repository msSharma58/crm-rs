<?php

declare(strict_types=1);

namespace App\Modules\Tasks\Application\Services;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

final class TaskService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::query()
            ->with(['assignee', 'creator', 'taskable'])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Task
    {
        return Task::with(['assignee', 'creator', 'comments.user', 'taskable'])->find($id);
    }

    public function create(array $data): Task
    {
        $data['created_by'] ??= Auth::id();

        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh();
    }

    public function complete(Task $task): Task
    {
        $task->update([
            'status' => 'done',
            'completed_at' => now(),
        ]);

        return $task->fresh();
    }

    public function addComment(Task $task, string $body): TaskComment
    {
        return $task->comments()->create([
            'user_id' => Auth::id(),
            'body' => $body,
        ]);
    }

    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }
}
