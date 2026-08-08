<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Services\ExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consultation des pièces jointes côté back-office.
 *
 * Les pièces ne sont pas dans `public/` : une preuve de consommateur — facture,
 * pièce d'identité photographiée par mégarde, enregistrement vocal — ne doit pas
 * être accessible à qui devine une URL. Elles transitent par ce contrôleur, qui
 * vérifie l'authentification, le périmètre de l'agent, et journalise l'accès
 * comme l'exige le §9.
 */
class AttachmentController extends Controller
{
    public function __construct(private ExtractionService $extraction) {}

    public function show(Request $request, string $locale, Attachment $attachment): StreamedResponse
    {
        $this->authorizeScope($request, $attachment);

        AuditLog::record(
            'view_attachment', 'attachment', $attachment->id,
            (string) $request->user()->id, $request->user()->name,
        );

        abort_unless(Storage::disk('local')->exists($attachment->storage_key), 404);

        // `inline` : l'agent regarde la photo ou écoute l'enregistrement dans le
        // navigateur, sans avoir à télécharger la pièce sur son poste.
        return Storage::disk('local')->response(
            $attachment->storage_key,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type],
            'inline',
        );
    }

    /**
     * Extrait le texte d'une image ou d'un enregistrement et le conserve, pour
     * que l'association destinataire lise le contenu sans ouvrir la pièce.
     */
    public function extract(Request $request, string $locale, Attachment $attachment): JsonResponse
    {
        $this->authorizeScope($request, $attachment);

        if (! $this->extraction->supports($attachment)) {
            return response()->json(['ok' => false, 'error' => 'type_non_supporte'], 422);
        }

        $resultat = $this->extraction->extract($attachment, $locale);

        AuditLog::record(
            'extract_attachment', 'attachment', $attachment->id,
            (string) $request->user()->id, $request->user()->name,
        );

        return response()->json($resultat, $resultat['ok'] ? 200 : 422);
    }

    /** Un agent ne consulte que les pièces des dossiers de son association. */
    private function authorizeScope(Request $request, Attachment $attachment): void
    {
        // Une pièce orpheline appartient encore à un brouillon : elle n'est
        // rattachée à aucun dossier, donc à aucun périmètre.
        abort_if($attachment->dossier_id === null, 404);

        $scope = $request->user()->visibleAssociationId();

        abort_if(
            $scope !== null && $attachment->dossier->assigned_association_id !== $scope,
            403,
        );
    }
}
