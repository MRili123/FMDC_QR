<?php

namespace Database\Seeders;

use App\Models\Dossier;
use App\Services\DossierService;
use Illuminate\Database\Seeder;

/**
 * Jeu de démonstration : de quoi montrer la file, les statuts, les délais et la
 * détection de signaux collectifs sans avoir à saisir dix dossiers à la main.
 */
class DemoSeeder extends Seeder
{
    private const DOSSIERS = [
        ['telecom', 'prix_errone', 'TelcoMaroc', 'rabat_sale_kenitra', 'RESOLU', 'Facturation doublée après un changement de forfait non demandé.'],
        ['telecom', 'tromperie', 'TelcoMaroc', 'rabat_sale_kenitra', 'EN_MEDIATION', 'Offre promotionnelle annoncée à 199 DH, facturée 349 DH dès le deuxième mois.'],
        ['telecom', 'mauvaise_qualite', 'TelcoMaroc', 'rabat_sale_kenitra', 'TRANSMIS_PROFESSIONNEL', 'Connexion fibre interrompue plus de vingt jours, aucune intervention malgré les relances.'],
        ['achat_magasin', 'defectueux', 'ElectroPlus', 'casablanca_settat', 'A_VERIFIER', 'Réfrigérateur en panne trois semaines après l\'achat, le magasin renvoie vers le fabricant.'],
        ['achat_magasin', 'garantie_refusee', 'ElectroPlus', 'casablanca_settat', 'INFOS_DEMANDEES', 'Garantie refusée au motif que le ticket de caisse est illisible.'],
        ['transport_livraison', 'non_recu', 'RapidColis', 'marrakech_safi', 'ORIENTE_ASSOCIATION', 'Colis marqué livré alors que rien n\'a été reçu, aucune signature fournie.'],
        ['banque_assurance', 'refus_remboursement', 'BanquePopulaire', 'casablanca_settat', 'TRANSMIS_AUTORITE', 'Prélèvement contesté non remboursé malgré une opposition déposée le jour même.'],
        ['eau_energie', 'prix_errone', 'RegieEau', 'fes_meknes', 'CLOTURE', 'Facture d\'eau multipliée par quatre sans relevé de compteur.'],
    ];

    public function run(DossierService $service): void
    {
        if (Dossier::count() > 0) {
            $this->command->warn('Des dossiers existent déjà : DemoSeeder ignoré.');

            return;
        }

        foreach (self::DOSSIERS as $i => [$categorie, $motif, $pro, $region, $status, $description]) {
            $result = $service->create([
                'demarche' => 'RECLAMATION',
                'categorie' => $categorie,
                'motif' => $motif,
                'description' => $description,
                'professionnel' => $pro,
                'region' => $region,
                'contact' => [
                    'telephone' => '06'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                    'nom' => 'Requérant démo '.($i + 1),
                    'phone_verified' => $i % 2 === 0,
                ],
            ]);

            $dossier = $result['dossier'];

            // Antidater pour que le délai médian de prise en charge ait du sens.
            $dossier->forceFill([
                'created_at' => now()->subDays(12 - $i),
                'first_handled_at' => now()->subDays(12 - $i)->addHours(2 + $i),
            ])->save();

            if ($status !== $dossier->status) {
                $service->transition($dossier, $status, 'Traitement de démonstration', true, null, 'Système');
            }

            $this->command->info("{$dossier->reference} — {$categorie} — jeton {$result['token']}");
        }
    }
}
