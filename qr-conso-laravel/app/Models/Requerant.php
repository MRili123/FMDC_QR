<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identité du requérant, séparée du contenu du dossier (§9). Absente lorsque le
 * signalement est anonyme.
 */
class Requerant extends Model
{
    use HasUlids;

    protected $fillable = [
        'dossier_id', 'telephone', 'whatsapp', 'email', 'nom', 'adresse', 'phone_verified_at',
    ];

    protected $hidden = ['adresse'];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
        ];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }
}
