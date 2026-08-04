<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Integrations\Facebook\FacebookLeadSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessFacebookLeadWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(FacebookLeadSyncService $service): void
    {
        $service->handleWebhook($this->payload);
    }
}
