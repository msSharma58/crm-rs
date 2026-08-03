<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Modules\Ai\Application\Services\AiLeadScoringService;
use Illuminate\Http\JsonResponse;

class AiController extends Controller
{
    public function __construct(
        private readonly AiLeadScoringService $scoringService,
    ) {}

    public function scoreLead(Lead $lead): JsonResponse
    {
        $score = $this->scoringService->score($lead->toArray());
        $lead->update(['ai_score' => $score]);

        return $this->success([
            'lead_id' => $lead->id,
            'ai_score' => $score,
        ], 'Lead scored');
    }
}
