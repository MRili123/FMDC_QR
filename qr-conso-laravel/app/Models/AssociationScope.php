<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Région ou secteur couvert par une association. Une ligne par valeur, ce qui
 * rend le périmètre interrogeable par le moteur de routage.
 */
class AssociationScope extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['association_id', 'kind', 'value'];

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }
}
