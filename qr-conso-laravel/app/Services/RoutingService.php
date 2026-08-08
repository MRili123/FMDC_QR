<?php

namespace App\Services;

use App\Models\Association;
use App\Models\RoutingRule;
use Illuminate\Support\Collection;

/**
 * Moteur d'orientation « aucune mauvaise porte » (§5).
 *
 * Deux critères, dans cet ordre : la compétence — l'association traite-t-elle ce
 * type de problème — puis la proximité — couvre-t-elle la région où les faits se
 * sont produits. Une association du bon secteur mais d'une autre région reste
 * préférable à une association proche qui ne connaît pas le sujet.
 *
 * Le moteur ne s'appuyait auparavant que sur des règles saisies à la main : sans
 * règle, le dossier tombait au bureau national même quand une association
 * déclarait explicitement le secteur concerné. Le périmètre déclaré
 * (AssociationScope) est désormais exploité, ce pour quoi il avait été créé.
 */
class RoutingService
{
    /** Une règle de routage explicite prime sur toute déduction. */
    private const POIDS_REGLE_REGIONALE = 100;

    private const POIDS_REGLE_NATIONALE = 60;

    /** L'association déclare le secteur concerné. */
    private const POIDS_SECTEUR = 40;

    /** L'association couvre la région où les faits se sont produits. */
    private const POIDS_REGION = 30;

    /**
     * Ne rejette jamais un dossier : sans candidat, renvoie null et le dossier
     * reste dans la file nationale plutôt que de bloquer la soumission.
     */
    public function resolveAssociation(string $categorie, ?string $region = null): ?string
    {
        $meilleur = $this->suggest($categorie, $region)->first();

        return $meilleur['association']->id ?? null;
    }

    /**
     * Classement des associations pour un problème donné, avec le détail de ce
     * qui a joué. L'agent doit pouvoir lire pourquoi telle association est
     * proposée, pas seulement la voir apparaître (§8 : l'outil propose, l'humain
     * décide).
     *
     * @return Collection<int, array{association: Association, score: int, raisons: list<string>}>
     */
    public function suggest(string $categorie, ?string $region = null, int $limite = 3): Collection
    {
        $secteur = config("taxonomy.category_sector.$categorie");

        $associations = Association::query()
            ->where('active', true)
            ->with('scopes')
            ->get();

        $regles = RoutingRule::query()
            ->where('categorie', $categorie)
            ->get()
            ->groupBy('association_id');

        return $associations
            ->map(function (Association $association) use ($regles, $secteur, $region) {
                $score = 0;
                $raisons = [];

                foreach ($regles->get($association->id, collect()) as $regle) {
                    if ($region && $regle->region === $region) {
                        $score += self::POIDS_REGLE_REGIONALE;
                        $raisons[] = 'regle_regionale';
                    } elseif ($regle->region === null) {
                        $score += self::POIDS_REGLE_NATIONALE;
                        $raisons[] = 'regle_nationale';
                    }
                }

                $portees = $association->scopes;

                if ($secteur && $portees->contains(fn ($p) => $p->kind === 'SECTEUR' && $p->value === $secteur)) {
                    $score += self::POIDS_SECTEUR;
                    $raisons[] = 'secteur';
                }

                if ($region && $portees->contains(fn ($p) => $p->kind === 'REGION' && $p->value === $region)) {
                    $score += self::POIDS_REGION;
                    $raisons[] = 'region';
                }

                return [
                    'association' => $association,
                    'score' => $score,
                    'raisons' => array_values(array_unique($raisons)),
                ];
            })
            ->filter(fn ($c) => $c['score'] > 0)
            // À score égal, l'ordre alphabétique évite qu'une association soit
            // systématiquement favorisée par son rang en base.
            ->sortBy([['score', 'desc'], fn ($a, $b) => strcmp($a['association']->nom, $b['association']->nom)])
            ->values()
            ->take($limite);
    }
}
