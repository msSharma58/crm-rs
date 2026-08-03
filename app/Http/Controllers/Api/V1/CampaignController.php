<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $campaigns = Campaign::query()
            ->withCount([
                'leads',
                'leads as converted_leads_count' => fn ($q) => $q->whereIn('status', [
                    LeadStatus::Sold->value,
                    LeadStatus::Booking->value,
                    LeadStatus::PaymentPending->value,
                ]),
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate((int) $request->get('per_page', 15));

        return $this->success(
            CampaignResource::collection($campaigns)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'channel' => ['required', 'string'],
            'budget' => ['nullable', 'numeric'],
            'spend' => ['nullable', 'numeric'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $campaign = Campaign::create($data)->loadCount([
            'leads',
            'leads as converted_leads_count' => fn ($q) => $q->whereIn('status', [
                LeadStatus::Sold->value,
                LeadStatus::Booking->value,
                LeadStatus::PaymentPending->value,
            ]),
        ]);

        return $this->success(new CampaignResource($campaign), 'Campaign created', 201);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        $campaign->loadCount([
            'leads',
            'leads as converted_leads_count' => fn ($q) => $q->whereIn('status', [
                LeadStatus::Sold->value,
                LeadStatus::Booking->value,
                LeadStatus::PaymentPending->value,
            ]),
        ]);

        return $this->success(new CampaignResource($campaign));
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'channel' => ['sometimes', 'string'],
            'budget' => ['nullable', 'numeric'],
            'spend' => ['nullable', 'numeric'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $campaign->update($data);
        $campaign->loadCount([
            'leads',
            'leads as converted_leads_count' => fn ($q) => $q->whereIn('status', [
                LeadStatus::Sold->value,
                LeadStatus::Booking->value,
                LeadStatus::PaymentPending->value,
            ]),
        ]);

        return $this->success(new CampaignResource($campaign), 'Campaign updated');
    }

    public function roi(Campaign $campaign): JsonResponse
    {
        $campaign->loadCount([
            'leads',
            'leads as converted_leads_count' => fn ($q) => $q->whereIn('status', [
                LeadStatus::Sold->value,
                LeadStatus::Booking->value,
                LeadStatus::PaymentPending->value,
            ]),
        ]);

        return $this->success(new CampaignResource($campaign));
    }
}
