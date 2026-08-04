<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Integrations\WhatsApp\WhatsAppCloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessWhatsAppWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function handle(WhatsAppCloudService $service): void
    {
        $service->handleInbound($this->payload);
    }
}
