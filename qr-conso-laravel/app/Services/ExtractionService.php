<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Transcription vocale et lecture de documents (§8), déléguées à Python.
 *
 * Ollama ne sert qu'au texte : la parole demande un modèle acoustique
 * (faster-whisper) et l'OCR un moteur dédié (Tesseract). Laravel les appelle en
 * sous-processus et lit une ligne de JSON — la même mécanique que n'importe
 * quelle commande système, sans service à maintenir en plus.
 *
 * L'intérêt pour la FMDC est le §6.3 : une association qui reçoit un dossier lit
 * le texte directement dans la fiche, au lieu d'avoir à ouvrir chaque pièce.
 */
class ExtractionService
{
    public function isEnabled(): bool
    {
        return (bool) config('qrconso.extraction.enabled');
    }

    /** Une pièce est exploitable si c'est une image ou un enregistrement. */
    public function supports(Attachment $piece): bool
    {
        return $this->kindOf($piece) !== null;
    }

    private function kindOf(Attachment $piece): ?string
    {
        return match (true) {
            $piece->isImage() => 'image',
            $piece->isAudio() => 'audio',
            default => null,
        };
    }

    /**
     * Extrait le texte et le range dans la pièce jointe.
     *
     * @return array{ok:bool, text?:string, engine?:string, error?:string}
     */
    public function extract(Attachment $piece, string $locale = 'fr'): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => false, 'error' => 'desactive'];
        }

        $kind = $this->kindOf($piece);
        if (! $kind) {
            return ['ok' => false, 'error' => 'type_non_supporte'];
        }

        $chemin = Storage::disk('local')->path($piece->storage_key);
        if (! is_file($chemin)) {
            return ['ok' => false, 'error' => 'fichier_introuvable'];
        }

        $process = new Process([
            config('qrconso.extraction.python'),
            base_path('python/extract_text.py'),
            '--file', $chemin,
            '--kind', $kind,
            '--lang', $locale,
            '--model', config('qrconso.extraction.whisper_model'),
        ], base_path());

        // La première transcription télécharge le modèle : le délai doit couvrir
        // ce cas, sinon la fonctionnalité paraît cassée au premier essai.
        $process->setTimeout(config('qrconso.extraction.timeout'));

        // Le serveur de développement de PHP transmet un environnement appauvri.
        // Sans SystemRoot, Windows ne parvient pas à initialiser Winsock et
        // Python meurt sur « WinError 10106 » — alors que la même commande
        // fonctionne dans un terminal. On rétablit le minimum vital.
        $process->setEnv(array_filter([
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'PATH' => getenv('PATH') ?: getenv('Path'),
            'TEMP' => getenv('TEMP'),
            'TMP' => getenv('TMP'),
            'USERPROFILE' => getenv('USERPROFILE'),
            // Cache des modèles Whisper, sinon relogé à chaque changement d'utilisateur.
            'HF_HOME' => getenv('HF_HOME'),
        ]));

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return ['ok' => false, 'error' => 'delai_depasse'];
        }

        $sortie = trim($process->getOutput());
        $donnees = json_decode($sortie, true);

        if (! is_array($donnees)) {
            // Assez long pour contenir une trace Python complète : tronquer à
            // 300 caractères ne laissait que le mot « Traceback ».
            Log::warning('Extraction : sortie illisible', [
                'sortie' => mb_substr($sortie, 0, 1000),
                'erreur' => mb_substr($process->getErrorOutput(), -2000),
            ]);

            return ['ok' => false, 'error' => 'sortie_illisible'];
        }

        if (empty($donnees['ok'])) {
            Log::warning('Extraction en échec', $donnees);

            return ['ok' => false, 'error' => $donnees['error'] ?? 'echec'];
        }

        $texte = trim($donnees['text'] ?? '');

        if ($texte === '') {
            return ['ok' => false, 'error' => 'texte_vide'];
        }

        $piece->update(['transcription' => $texte]);

        return [
            'ok' => true,
            'text' => $texte,
            'engine' => $donnees['engine'] ?? null,
        ];
    }
}
