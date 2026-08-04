<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\IntegrationMessageResource;
use App\Models\IntegrationMessage;
use App\Models\Lead;
use App\Models\Organization;
use App\Modules\Integrations\Application\Services\IntegrationSettingsService;
use App\Modules\Integrations\Facebook\FacebookLeadSyncService;
use App\Modules\Integrations\WhatsApp\WhatsAppCloudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function __construct(
        private readonly IntegrationSettingsService $settingsService,
        private readonly FacebookLeadSyncService $facebookLeadSyncService,
        private readonly WhatsAppCloudService $whatsAppCloudService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $organization = $this->organization($request);

        return $this->success($this->settingsService->listForOrganization($organization));
    }

    public function upsert(Request $request, string $provider): JsonResponse
    {
        abort_unless(in_array($provider, ['facebook', 'whatsapp'], true), 404);

        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'page_access_token' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
            'app_secret' => ['nullable', 'string'],
            'page_id' => ['nullable', 'string'],
            'page_ids' => ['nullable', 'array'],
            'phone_number_id' => ['nullable', 'string'],
            'display_phone_number' => ['nullable', 'string'],
            'waba_id' => ['nullable', 'string'],
            'default_form_id' => ['nullable', 'string'],
            'webhook_verify_token' => ['nullable', 'string'],
        ]);

        $setting = $this->settingsService->upsert($this->organization($request), $provider, $data);

        return $this->success($setting, 'Integration settings saved');
    }

    public function syncFacebookForm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'form_id' => ['required', 'string'],
        ]);

        $result = $this->facebookLeadSyncService->syncForm(
            $this->organization($request),
            $data['form_id'],
        );

        return $this->success($result, 'Facebook form sync completed');
    }

    public function sendWhatsApp(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4096'],
        ]);

        abort_unless((int) $lead->organization_id === (int) $request->user()->organization_id
            || $request->user()->isSuperAdmin(), 403);

        $message = $this->whatsAppCloudService->sendText($lead, $data['message']);

        return $this->success(new IntegrationMessageResource($message), 'WhatsApp message sent', 201);
    }

    public function leadMessages(Request $request, Lead $lead): JsonResponse
    {
        abort_unless((int) $lead->organization_id === (int) $request->user()->organization_id
            || $request->user()->isSuperAdmin(), 403);

        $messages = IntegrationMessage::query()
            ->where('lead_id', $lead->id)
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        return $this->success(IntegrationMessageResource::collection($messages)->resolve());
    }

    private function organization(Request $request): Organization
    {
        $organization = $request->user()->organization;
        abort_if($organization === null, 422, 'No organization context.');

        return $organization;
    }
}
