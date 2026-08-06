<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Association extends Model
{
    use HasUlids;

    protected $fillable = ['nom', 'contact', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(AssociationScope::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(RoutingRule::class);
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class, 'assigned_association_id');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return list<string> */
    public function regions(): array
    {
        return $this->scopes->where('kind', 'REGION')->pluck('value')->all();
    }

    /** @return list<string> */
    public function secteurs(): array
    {
        return $this->scopes->where('kind', 'SECTEUR')->pluck('value')->all();
    }
}
