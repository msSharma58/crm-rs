<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $leadsCount = (int) ($this->leads_count ?? $this->leads()->count());
        $convertedCount = (int) ($this->converted_leads_count ?? $this->leads()
            ->whereIn('status', [LeadStatus::Sold->value, LeadStatus::Booking->value, LeadStatus::PaymentPending->value])
            ->count());
        $spend = (float) $this->spend;
        $budget = (float) $this->budget;
        $costPerLead = $leadsCount > 0 ? round($spend / $leadsCount, 2) : 0.0;
        $conversionRate = $leadsCount > 0 ? round(($convertedCount / $leadsCount) * 100, 2) : 0.0;
        // Simple ROI proxy: converted value assumed vs spend (when spend > 0)
        $roi = $spend > 0 ? round((($convertedCount * 100000) - $spend) / $spend * 100, 2) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'channel' => $this->channel,
            'budget' => $this->budget,
            'spend' => $this->spend,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'status' => $this->status,
            'leads_count' => $leadsCount,
            'converted_count' => $convertedCount,
            'conversion_rate' => $conversionRate,
            'cost_per_lead' => $costPerLead,
            'budget_utilization' => $budget > 0 ? round(($spend / $budget) * 100, 2) : 0.0,
            'roi' => $roi,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
