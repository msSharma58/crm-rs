<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\Lead;
use App\Modules\Automation\Application\Services\AutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationController extends Controller
{
    public function __construct(
        private readonly AutomationService $automationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $rules = AutomationRule::query()
            ->when($request->filled('trigger'), fn ($q) => $q->where('trigger', $request->input('trigger')))
            ->paginate((int) $request->get('per_page', 15));

        return $this->success($rules);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'trigger' => ['required', 'string'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['required', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule = AutomationRule::create($data);

        return $this->success($rule, 'Automation rule created', 201);
    }

    public function run(AutomationRule $automationRule, Lead $lead): JsonResponse
    {
        $run = $this->automationService->executeRule($automationRule, $lead);

        return $this->success($run, 'Automation executed');
    }
}
