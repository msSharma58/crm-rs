<?php

declare(strict_types=1);

namespace App\Modules\Ai\Application\Services;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;

final class AiLeadScoringService
{
    public function score(array $leadData): int
    {
        $score = 30;

        if (! empty($leadData['budget'])) {
            $budget = (float) $leadData['budget'];
            if ($budget >= 10000000) {
                $score += 25;
            } elseif ($budget >= 5000000) {
                $score += 15;
            } elseif ($budget >= 2000000) {
                $score += 10;
            }
        }

        if (! empty($leadData['email'])) {
            $score += 5;
        }

        if (! empty($leadData['project_id'])) {
            $score += 10;
        }

        $source = $leadData['source'] ?? null;
        if ($source instanceof LeadSource) {
            $source = $source->value;
        }

        $score += match ($source) {
            LeadSource::Referral->value => 15,
            LeadSource::WalkIn->value => 12,
            LeadSource::Website->value, LeadSource::LandingPage->value => 8,
            LeadSource::Facebook->value, LeadSource::WhatsApp->value => 5,
            default => 0,
        };

        $priority = $leadData['priority'] ?? null;
        if ($priority instanceof LeadPriority) {
            $priority = $priority->value;
        }

        $score += match ($priority) {
            LeadPriority::Urgent->value => 10,
            LeadPriority::High->value => 7,
            LeadPriority::Medium->value => 3,
            default => 0,
        };

        return min(100, max(0, $score));
    }
}
