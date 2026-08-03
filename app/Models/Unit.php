<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Audit\Auditable;
use App\Core\Tenancy\BelongsToOrganization;
use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use Auditable;
    use BelongsToOrganization;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'project_id',
        'building_id',
        'floor_id',
        'code',
        'type',
        'area_sqft',
        'price',
        'status',
        'attributes',
    ];

    protected function casts(): array
    {
        return [
            'area_sqft' => 'decimal:2',
            'price' => 'decimal:2',
            'status' => UnitStatus::class,
            'attributes' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function mediaAssets(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'mediable');
    }
}
