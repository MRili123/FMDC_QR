<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUlids;

    protected $fillable = ['actor_id', 'actor_label', 'action', 'entity_type', 'entity_id'];

    public static function record(string $action, string $entityType, string $entityId, ?string $actorId = null, ?string $actorLabel = null): void
    {
        static::create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'actor_id' => $actorId,
            'actor_label' => $actorLabel,
        ]);
    }
}
