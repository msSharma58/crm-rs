<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Contracts;

use App\Models\Lead;

interface LeadIngestor
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(array $payload): ?Lead;
}
