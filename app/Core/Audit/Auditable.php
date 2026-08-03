<?php

declare(strict_types=1);

namespace App\Core\Audit;

use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            app(AuditLogger::class)->log('created', $model, null, $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();

            if ($changes === []) {
                return;
            }

            $oldValues = [];
            foreach (array_keys($changes) as $key) {
                $oldValues[$key] = $model->getOriginal($key);
            }

            app(AuditLogger::class)->log('updated', $model, $oldValues, $changes);
        });

        static::deleted(function (Model $model): void {
            app(AuditLogger::class)->log('deleted', $model, $model->getAttributes(), null);
        });
    }
}
