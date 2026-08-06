<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal append-only : alimente la frise publique de suivi et la traçabilité
 * interne. `public_note` distingue ce que le consommateur peut lire.
 */
class DossierEvent extends Model
{
    use HasUlids;

    protected $fillable = [
        'dossier_id', 'from_status', 'to_status', 'note', 'public_note', 'actor_id', 'actor_label',
    ];

    protected function casts(): array
    {
        return ['public_note' => 'boolean'];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }
}
