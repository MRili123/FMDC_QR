<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Couche d'IA d'assistance (§8), servie par un modèle local via Ollama. Aucun
 * abonnement à une API tierce n'est nécessaire, et aucune donnée de réclamation
 * ne quitte la machine — ce qui sert aussi les exigences du §9.
 *
 * L'IA ne décide jamais : elle propose. Le consommateur valide le résumé avant
 * envoi et la qualification juridique reste humaine.
 *
 * Les trois services du §8 (reformulation, classification, pièces manquantes)
 * tiennent dans UNE seule génération. En trois appels séparés le parcours
 * attendait ~47 s, parce qu'Ollama sérialise les requêtes sur un même modèle et
 * que le coût est dominé par les jetons produits.
 */
class OllamaService
{
    public function isEnabled(): bool
    {
        return (bool) config('qrconso.ai.enabled');
    }

    /** Le modèle tourne-t-il réellement ? Utilisé pour dégrader l'UI proprement. */
    public function isAvailable(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            return Http::timeout(3)->get(config('qrconso.ai.url').'/api/version')->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Analyse complète d'un récit de consommateur.
     *
     * @param  list<string>  $piecesFournies
     * @return array{resume:?string, classification:?array, manquant:?list<string>}
     */
    public function analyse(
        string $recit,
        string $locale = 'fr',
        ?string $resultatAttendu = null,
        array $piecesFournies = [],
    ): array {
        $vide = ['resume' => null, 'classification' => null, 'manquant' => null];

        if (! $this->isEnabled()) {
            return $vide;
        }

        $langue = $locale === 'ar' ? 'arabe' : 'français';
        $categories = implode(', ', config('taxonomy.categories'));
        $motifs = implode(', ', config('taxonomy.motifs'));
        $pieces = $piecesFournies ? implode(', ', $piecesFournies) : 'aucune';
        $attendu = $resultatAttendu ?: 'non précisé';

        $prompt = <<<PROMPT
        Tu assistes une association marocaine de défense des consommateurs.
        Analyse le récit ci-dessous et réponds par un objet JSON unique.

        Champs attendus :
        - "resume" : reformulation factuelle et neutre en {$langue}, 3 phrases maximum.
          N'invente aucun fait, aucune date, aucun montant. Pas de qualification juridique.
        - "categorie" : une valeur parmi {$categories}
        - "motif" : une valeur parmi {$motifs}
        - "urgence" : faible, moyenne ou haute
        - "pieces" : tableau de 0 à 3 pièces justificatives manquantes, formulées en français,
          par exemple ["La facture d'achat"]. Tableau vide si rien ne manque.

        Contexte : résultat attendu = {$attendu}. Pièces déjà fournies = {$pieces}.

        Récit du consommateur :
        {$recit}
        PROMPT;

        $raw = $this->generate($prompt, 400, format: 'json');
        if (! $raw) {
            return $vide;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $vide;
        }

        return [
            'resume' => $this->cleanString($decoded['resume'] ?? null),
            'classification' => $this->cleanClassification($decoded),
            'manquant' => $this->cleanPieces($decoded['pieces'] ?? null),
        ];
    }

    private function cleanString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Le modèle peut proposer une valeur hors taxonomie : on ne garde que ce qui
     * existe réellement, sinon le routage partirait sur une clé morte.
     *
     * @param  array<string,mixed>  $decoded
     */
    private function cleanClassification(array $decoded): ?array
    {
        $result = [];

        if (in_array($decoded['categorie'] ?? null, config('taxonomy.categories'), true)) {
            $result['categorie'] = $decoded['categorie'];
        }
        if (in_array($decoded['motif'] ?? null, config('taxonomy.motifs'), true)) {
            $result['motif'] = $decoded['motif'];
        }
        if (in_array($decoded['urgence'] ?? null, ['faible', 'moyenne', 'haute'], true)) {
            $result['urgence'] = $decoded['urgence'];
        }

        return $result ?: null;
    }

    /** @return list<string>|null */
    private function cleanPieces(mixed $value): ?array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return null;
        }

        $pieces = array_values(array_slice(array_filter($value, 'is_string'), 0, 3));

        return $pieces ?: null;
    }

    private function generate(string $prompt, int $maxTokens, ?string $format = null): ?string
    {
        $payload = [
            'model' => config('qrconso.ai.model'),
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                // Température basse : on veut de la restitution, pas de la créativité.
                'temperature' => 0.2,
                'num_predict' => $maxTokens,
            ],
        ];

        if ($format) {
            $payload['format'] = $format;
        }

        try {
            $response = Http::timeout(config('qrconso.ai.timeout'))
                ->post(config('qrconso.ai.url').'/api/generate', $payload);

            if (! $response->successful()) {
                Log::warning('Ollama a répondu en erreur', ['status' => $response->status()]);

                return null;
            }

            $text = trim($response->json('response') ?? '');

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Ollama injoignable', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
