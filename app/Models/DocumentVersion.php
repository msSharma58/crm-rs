<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Tenancy\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'document_id',
        'uploaded_by',
        'version',
        'path',
        'disk',
        'size',
        'mime',
        'change_note',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'size' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
