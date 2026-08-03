<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Tenancy\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitReport extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'visit_id',
        'user_id',
        'outcome',
        'summary',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'feedback' => 'array',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
