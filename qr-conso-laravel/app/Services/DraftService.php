<?php

namespace App\Services;

use App\Support\Signer;
use Illuminate\Support\Facades\Session;

/**
 * État du parcours en cours. Il vit en session plutôt qu'en base : tant que le
 * consommateur n'a pas validé, rien n'est écrit côté FMDC — ce qui est le
 * comportement attendu par la minimisation des données du §9.
 *
 * Le `draft_id` sert uniquement à rattacher les pièces téléversées avant que le
 * dossier n'existe.
 */
class DraftService
{
    private const KEY = 'reclamation_draft';

    /** @return array<string,mixed> */
    public function all(): array
    {
        $draft = Session::get(self::KEY);

        if (! is_array($draft) || empty($draft['draft_id'])) {
            $draft = ['draft_id' => Signer::draftId()];
            Session::put(self::KEY, $draft);
        }

        return $draft;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /** @param array<string,mixed> $values */
    public function merge(array $values): void
    {
        Session::put(self::KEY, array_merge($this->all(), $values));
    }

    public function draftId(): string
    {
        return $this->all()['draft_id'];
    }

    public function forget(): void
    {
        Session::forget(self::KEY);
    }

    /**
     * Le parcours est linéaire : chaque écran vérifie que le précédent est
     * rempli, sinon on renvoie l'utilisateur là où il s'est arrêté.
     */
    public function firstMissingStep(): ?string
    {
        $draft = $this->all();

        return match (true) {
            empty($draft['demarche']) => 'demarche',
            empty($draft['categorie']) => 'categorie',
            empty($draft['motif']) => 'motif',
            default => null,
        };
    }
}
