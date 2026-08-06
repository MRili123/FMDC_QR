<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dossier extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'reference', 'demarche', 'categorie', 'motif', 'description',
        'resultat_attendu', 'status', 'region', 'etablissement', 'professionnel',
        'tracking_token_hash', 'first_handled_at', 'assigned_association_id', 'qr_code_id',
    ];

    protected function casts(): array
    {
        return [
            'first_handled_at' => 'datetime',
        ];
    }

    public function requerant(): HasOne
    {
        return $this->hasOne(Requerant::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DossierEvent::class);
    }

    public function assignedAssociation(): BelongsTo
    {
        return $this->belongsTo(Association::class, 'assigned_association_id');
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    /**
     * Un dossier est « ouvert » tant qu'il n'est ni résolu ni clôturé.
     */
    public function isOpen(): bool
    {
        return ! in_array($this->status, ['RESOLU', 'CLOTURE'], true);
    }
}
