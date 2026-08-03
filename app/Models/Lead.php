<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Audit\Auditable;
use App\Core\Tenancy\BelongsToOrganization;
use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use Auditable;
    use BelongsToOrganization;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'phone',
        'email',
        'location',
        'budget',
        'preferred_property',
        'project_id',
        'source',
        'campaign_id',
        'status',
        'assigned_to',
        'priority',
        'ai_score',
        'notes',
        'last_contacted_at',
        'converted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
            'priority' => LeadPriority::class,
            'ai_score' => 'integer',
            'last_contacted_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'taggables', 'taggable_id', 'tag_id')
            ->where('taggables.taggable_type', self::class)
            ->withTimestamps();
    }

    public function customer(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
