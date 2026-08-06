<?php

namespace App\Services;

use App\Models\RoutingRule;

class RoutingService
{
    /**
     * Principe « aucune mauvaise porte » (§5) : cette méthode ne rejette jamais
     * un dossier. Faute de règle correspondante elle renvoie null, ce qui laisse
     * le dossier dans la file nationale FMDC plutôt que de bloquer la soumission.
     *
     * La signature accepte exactement ce qu'un classifieur IA produirait, pour
     * que la suggestion d'Ollama puisse alimenter l'affectation sans la réécrire.
     */
    public function resolveAssociation(string $categorie, ?string $region = null): ?string
    {
        $rules = RoutingRule::query()
            ->where('categorie', $categorie)
            ->whereHas('association', fn ($q) => $q->where('active', true))
            ->when($region, fn ($q) => $q->where(
                fn ($sub) => $sub->where('region', $region)->orWhereNull('region')
            ))
            ->orderBy('priority')
            ->get();

        if ($rules->isEmpty()) {
            return null;
        }

        // Une règle qui nomme la région l'emporte sur une règle nationale de même priorité.
        $regional = $region ? $rules->firstWhere('region', $region) : null;

        return ($regional ?? $rules->first())->association_id;
    }
}
