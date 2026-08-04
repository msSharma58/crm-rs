<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFacebookLeadWebhook;
use App\Modules\Integrations\Facebook\FacebookLeadSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FacebookWebhookController extends Controller
{
    public function __construct(
        private readonly FacebookLeadSyncService $facebookLeadSyncService,
    ) {}

    public function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        $result = $this->facebookLeadSyncService->verifyWebhook($mode, $token, $challenge);

        if ($result === null) {
            return response('Invalid verify token', 403);
        }

        return response($result, 200)->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        ProcessFacebookLeadWebhook::dispatch($payload);

        return response()->json(['success' => true]);
    }
}
