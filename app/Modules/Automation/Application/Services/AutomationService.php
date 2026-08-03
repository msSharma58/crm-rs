<?php

declare(strict_types=1);

namespace App\Modules\Automation\Application\Services;

use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

final class AutomationService
{
    public function handleLeadCreated(Lead $lead): void
    {
        $rules = AutomationRule::query()
            ->where('trigger', 'lead_created')
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $this->executeRule($rule, $lead);
        }
    }

    public function executeRule(AutomationRule $rule, Lead $lead): AutomationRun
    {
        $result = ['actions_executed' => []];

        foreach ($rule->actions as $action) {
            $type = $action['type'] ?? null;

            match ($type) {
                'create_follow_up' => $this->createFollowUp($lead, $action, $result),
                'create_task' => $this->createTask($lead, $action, $result),
                'assign_user' => $this->assignUser($lead, $action, $result),
                default => $result['actions_executed'][] = ['type' => $type, 'status' => 'skipped'],
            };
        }

        return AutomationRun::create([
            'automation_rule_id' => $rule->id,
            'subject_type' => Lead::class,
            'subject_id' => $lead->id,
            'status' => 'success',
            'result' => $result,
        ]);
    }

    private function createFollowUp(Lead $lead, array $action, array &$result): void
    {
        $followUp = FollowUp::create([
            'lead_id' => $lead->id,
            'assigned_to' => $lead->assigned_to ?? Auth::id(),
            'channel' => $action['channel'] ?? 'call',
            'title' => $action['title'] ?? 'Follow up new lead',
            'due_at' => now()->addHours($action['due_in_hours'] ?? 24),
            'status' => 'pending',
            'priority' => $action['priority'] ?? 'medium',
        ]);

        $result['actions_executed'][] = [
            'type' => 'create_follow_up',
            'status' => 'success',
            'follow_up_id' => $followUp->id,
        ];
    }

    private function createTask(Lead $lead, array $action, array &$result): void
    {
        $task = Task::create([
            'assigned_to' => $lead->assigned_to ?? Auth::id(),
            'created_by' => Auth::id(),
            'taskable_type' => Lead::class,
            'taskable_id' => $lead->id,
            'title' => $action['title'] ?? 'Review new lead',
            'description' => $action['description'] ?? null,
            'priority' => $action['priority'] ?? 'medium',
            'status' => 'todo',
            'due_at' => now()->addDays($action['due_in_days'] ?? 1),
        ]);

        $result['actions_executed'][] = [
            'type' => 'create_task',
            'status' => 'success',
            'task_id' => $task->id,
        ];
    }

    private function assignUser(Lead $lead, array $action, array &$result): void
    {
        if (! empty($action['user_id'])) {
            $lead->update(['assigned_to' => $action['user_id']]);
        }

        $result['actions_executed'][] = [
            'type' => 'assign_user',
            'status' => 'success',
            'user_id' => $action['user_id'] ?? null,
        ];
    }
}
