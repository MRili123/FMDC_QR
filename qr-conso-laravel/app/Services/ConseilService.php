<?php

namespace App\Services;

/**
 * Le conseil du §7 : une réponse pédagogique appuyée sur les fiches juridiques
 * de la FMDC, et sur rien d'autre.
 *
 * Un modèle local interrogé à froid sur le droit marocain invente des numéros
 * d'articles et des délais plausibles mais faux. On lui fournit donc les fiches
 * pertinentes et on lui interdit de sortir de ce corpus : hors de là, il doit
 * dire qu'il ne sait pas et renvoyer vers la Fédération.
 */
class ConseilService
{
    public function __construct(private OllamaService $ollama) {}

    public function isAvailable(): bool
    {
        return $this->ollama->isAvailable();
    }

    /**
     * Sélection des fiches par recouvrement de mots-clés. Le corpus tient en
     * quelques fiches : une recherche vectorielle serait ici de la complication
     * sans bénéfice.
     *
     * @return list<array<string,mixed>>
     */
    public function fichesPertinentes(string $question): array
    {
        $normalisee = $this->normalise($question);
        $scores = [];

        foreach (config('conseil.fiches') as $index => $fiche) {
            $score = 0;
            foreach ($fiche['motscles'] as $mot) {
                if (str_contains($normalisee, $this->normalise($mot))) {
                    // Une expression de plusieurs mots est un signal plus fort
                    // qu'un mot isolé qui peut apparaître par hasard.
                    $score += str_contains($mot, ' ') ? 3 : 1;
                }
            }
            if ($score > 0) {
                $scores[$index] = $score;
            }
        }

        arsort($scores);
        $retenues = array_slice(array_keys($scores), 0, config('conseil.max_fiches'), true);
        $fiches = config('conseil.fiches');

        // Tableau vide = question hors corpus. L'appelant doit alors répondre
        // sans le modèle : voir estCouvert().
        return array_map(fn ($i) => $fiches[$i], $retenues);
    }

    /**
     * Une question est traitable si au moins une fiche la recouvre.
     *
     * Ce garde-fou est délibérément dans le code et non dans le prompt. Interdire
     * au modèle d'extrapoler ne suffit pas : interrogé sur les sanctions pénales,
     * qu'aucune fiche ne couvre, qwen2.5:7b répondait « la loi ne prévoit pas de
     * sanction spécifique » — une affirmation fausse sur le droit marocain,
     * produite avec aplomb. Hors corpus, on ne le laisse donc pas parler.
     */
    public function estCouvert(string $question): bool
    {
        return $this->fichesPertinentes($question) !== [];
    }

    /**
     * Réponse en flux : le modèle local produit une dizaine de jetons par
     * seconde, donc attendre la réponse complète donnerait vingt secondes
     * d'écran figé. Le texte s'affiche au fil de l'eau.
     *
     * @param  list<array<string,mixed>>  $fiches
     * @return iterable<string>
     */
    public function repondreEnFlux(string $question, array $fiches, string $locale = 'fr'): iterable
    {
        return $this->ollama->generateStream($this->prompt($question, $fiches, $locale));
    }

    /** @param list<array<string,mixed>> $fiches */
    private function prompt(string $question, array $fiches, string $locale): string
    {
        $langue = $locale === 'ar' ? 'arabe' : 'français';

        $corpus = collect($fiches)
            ->map(fn ($f) => "### {$f['titre']}\n{$f['contenu']}\n(Source : {$f['source']})")
            ->implode("\n\n");

        return <<<PROMPT
        Tu es l'assistant d'information de la Fédération Marocaine des Droits du Consommateur.
        Tu réponds à un consommateur marocain, en {$langue}, dans une langue simple et courte.

        RÈGLES ABSOLUES :
        - Réponds UNIQUEMENT à partir des fiches ci-dessous.
        - N'invente jamais un délai, un chiffre, un numéro d'article ou une procédure qui n'y figure pas.
        - N'affirme rien sur ce que la loi prévoit OU NE PRÉVOIT PAS en dehors des fiches.
          Dire « la loi ne prévoit pas de sanction » est aussi faux qu'inventer une sanction.
        - Si les fiches ne couvrent pas la question, écris simplement que cette information ne
          figure pas dans les fiches de la FMDC, et invite la personne à déposer une demande
          auprès de la Fédération. N'essaie pas de deviner ni de compléter.
        - Reproduis les délais MOT POUR MOT. « sept jours ouvrables » n'est pas « sept jours » :
          en arabe, écris « سبعة أيام عمل » et non « سبعة أيام ».
        - Ne dis jamais au consommateur qu'il va gagner, ni ce qu'un tribunal décidera.
        - Pas de formule d'ouverture ni de signature : va droit au fait.
        - Six phrases au maximum.

        FICHES DE LA FMDC :
        {$corpus}

        QUESTION DU CONSOMMATEUR :
        {$question}
        PROMPT;
    }

    private function normalise(string $texte): string
    {
        $texte = mb_strtolower($texte, 'UTF-8');

        return strtr($texte, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
    }
}
