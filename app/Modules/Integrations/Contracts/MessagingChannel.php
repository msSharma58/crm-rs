<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Contracts;

use App\Models\IntegrationMessage;
use App\Models\Lead;

interface MessagingChannel
{
    public function sendText(Lead $lead, string $message): IntegrationMessage;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleInbound(array $payload): void;
}
