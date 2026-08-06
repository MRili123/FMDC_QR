<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Dossier;
use App\Models\DossierEvent;
use App\Models\Requerant;
use App\Support\Signer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DossierService
{
    public function __construct(private RoutingService $routing) {}

    /**
     * Crée le dossier, l'oriente, journalise l'événement initial et rattache les
     * pièces téléversées pendant le brouillon.
     *
     * @param  array<string,mixed>  $input
     * @return array{dossier: Dossier, token: string}
     */
    public function create(array $input): array
    {
        $trackingToken = Signer::randomToken();
        $associationId = $this->routing->resolveAssociation(
            $input['categorie'],
            $input['region'] ?? null,
        );

        // Deux soumissions simultanées peuvent calculer la même référence ;
        // l'index unique les départage et on recalcule.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $dossier = DB::transaction(function () use ($input, $associationId, $trackingToken) {
                    $created = Dossier::create([
                        'reference' => $this->nextReference(),
                        'demarche' => $input['demarche'],
                        'categorie' => $input['categorie'],
                        'motif' => $input['motif'],
                        'description' => $input['description'] ?? null,
                        'resultat_attendu' => $input['resultat_attendu'] ?? null,
                        'region' => $input['region'] ?? null,
                        'etablissement' => $input['etablissement'] ?? null,
                        'professionnel' => $input['professionnel'] ?? null,
                        'qr_code_id' => $input['qr_code_id'] ?? null,
                        'tracking_token_hash' => Signer::hmac($trackingToken),
                        'assigned_association_id' => $associationId,
                        'status' => $associationId ? 'ORIENTE_ASSOCIATION' : 'RECU',
                    ]);

                    DossierEvent::create([
                        'dossier_id' => $created->id,
                        'to_status' => $created->status,
                        'note' => $associationId
                            ? 'Dossier orienté automatiquement'
                            : 'Dossier reçu',
                        'actor_label' => 'Système',
                    ]);

                    $contact = $input['contact'] ?? null;
                    if ($contact && array_filter([
                        $contact['telephone'] ?? null,
                        $contact['whatsapp'] ?? null,
                        $contact['email'] ?? null,
                        $contact['nom'] ?? null,
                    ])) {
                        Requerant::create([
                            'dossier_id' => $created->id,
                            'telephone' => $contact['telephone'] ?? null,
                            'whatsapp' => $contact['whatsapp'] ?? null,
                            'email' => $contact['email'] ?? null,
                            'nom' => $contact['nom'] ?? null,
                            'adresse' => $contact['adresse'] ?? null,
                            'phone_verified_at' => ($contact['phone_verified'] ?? false) ? now() : null,
                        ]);
                    }

                    if (! empty($input['draft_id'])) {
                        Attachment::where('draft_id', $input['draft_id'])
                            ->whereNull('dossier_id')
                            ->update(['dossier_id' => $created->id, 'draft_id' => null]);
                    }

                    return $created;
                });

                return ['dossier' => $dossier, 'token' => $trackingToken];
            } catch (QueryException $e) {
                // 1062 = doublon sur l'index unique de `reference`.
                if (! str_contains($e->getMessage(), '1062')) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Could not allocate a dossier reference');
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $count = Dossier::whereYear('created_at', $year)->count();

        return sprintf('FMDC-%d-%06d', $year, $count + 1);
    }

    /**
     * Transition de statut journalisée. `first_handled_at` marque la première
     * prise en charge humaine, ce qui alimente l'indicateur de délai du §13.
     */
    public function transition(
        Dossier $dossier,
        string $toStatus,
        ?string $note,
        bool $publicNote,
        ?string $actorId,
        ?string $actorLabel,
    ): void {
        DB::transaction(function () use ($dossier, $toStatus, $note, $publicNote, $actorId, $actorLabel) {
            $from = $dossier->status;

            $dossier->status = $toStatus;
            if (! $dossier->first_handled_at && $from === 'RECU') {
                $dossier->first_handled_at = now();
            }
            $dossier->save();

            DossierEvent::create([
                'dossier_id' => $dossier->id,
                'from_status' => $from,
                'to_status' => $toStatus,
                'note' => $note,
                'public_note' => $publicNote,
                'actor_id' => $actorId,
                'actor_label' => $actorLabel,
            ]);
        });
    }
}
