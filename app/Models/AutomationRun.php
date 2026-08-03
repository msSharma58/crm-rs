<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Tenancy\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AutomationRun extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'automation_rule_id',
        'subject_type',
        'subject_id',
        'status',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
        ];
    }

    public function automationRule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
