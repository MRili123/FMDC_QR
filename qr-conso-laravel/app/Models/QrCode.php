<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrCode extends Model
{
    use HasUlids;

    protected $fillable = [
        'code', 'type', 'signature', 'libelle', 'secteur', 'region',
        'etablissement', 'support', 'active', 'scan_count', 'reported_count',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }
}
