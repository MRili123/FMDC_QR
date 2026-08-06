<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutingRule extends Model
{
    use HasUlids;

    protected $fillable = ['categorie', 'region', 'priority', 'association_id'];

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }
}
