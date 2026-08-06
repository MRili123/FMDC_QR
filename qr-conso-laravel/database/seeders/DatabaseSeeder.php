<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\AssociationScope;
use App\Models\QrCode;
use App\Models\RoutingRule;
use App\Models\User;
use App\Support\Signer;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Associations et règles fictives : la liste réelle des associations
     * affiliées, de leurs régions et de leurs secteurs reste à confirmer par la
     * FMDC.
     */
    private const ASSOCIATIONS = [
        [
            'nom' => 'FMDC — Bureau national',
            'regions' => [],
            'secteurs' => [],
            'contact' => 'contact@consommateurs.ma',
            'categories' => ['autre', 'service_public', 'education', 'logement'],
        ],
        [
            'nom' => 'Association Casablanca-Settat des consommateurs',
            'regions' => ['casablanca_settat'],
            'secteurs' => ['commerce', 'ecommerce', 'banque_assurance'],
            'contact' => 'casablanca@consommateurs.ma',
            'categories' => ['achat_magasin', 'achat_internet', 'banque_assurance'],
        ],
        [
            'nom' => 'Association Rabat-Salé-Kénitra des consommateurs',
            'regions' => ['rabat_sale_kenitra'],
            'secteurs' => ['telecom', 'transport', 'eau_energie'],
            'contact' => 'rabat@consommateurs.ma',
            'categories' => ['telecom', 'transport_livraison', 'eau_energie'],
        ],
        [
            'nom' => 'Association Marrakech-Safi des consommateurs',
            'regions' => ['marrakech_safi'],
            'secteurs' => ['tourisme', 'commerce', 'sante'],
            'contact' => 'marrakech@consommateurs.ma',
            'categories' => ['tourisme_restauration', 'sante', 'achat_magasin'],
        ],
    ];

    public function run(): void
    {
        $byName = [];

        foreach (self::ASSOCIATIONS as $item) {
            $association = Association::firstOrCreate(
                ['nom' => $item['nom']],
                ['contact' => $item['contact']],
            );
            $byName[$item['nom']] = $association;

            foreach ($item['regions'] as $value) {
                AssociationScope::firstOrCreate([
                    'association_id' => $association->id,
                    'kind' => 'REGION',
                    'value' => $value,
                ]);
            }
            foreach ($item['secteurs'] as $value) {
                AssociationScope::firstOrCreate([
                    'association_id' => $association->id,
                    'kind' => 'SECTEUR',
                    'value' => $value,
                ]);
            }

            foreach ($item['categories'] as $categorie) {
                $region = $item['regions'][0] ?? null;
                RoutingRule::firstOrCreate(
                    [
                        'categorie' => $categorie,
                        'region' => $region,
                        'association_id' => $association->id,
                    ],
                    // Une règle régionale doit primer sur la règle nationale.
                    ['priority' => $region ? 10 : 100],
                );
            }
        }

        User::firstOrCreate(
            ['email' => 'admin@consommateurs.ma'],
            [
                'name' => 'Administrateur FMDC',
                'password' => 'Fmdc2026!',
                'role' => 'FMDC_ADMIN',
                'association_id' => $byName['FMDC — Bureau national']->id,
            ],
        );

        User::firstOrCreate(
            ['email' => 'agent.casa@consommateurs.ma'],
            [
                'name' => 'Agent Casablanca',
                'password' => 'Fmdc2026!',
                'role' => 'ASSOCIATION_AGENT',
                'association_id' => $byName['Association Casablanca-Settat des consommateurs']->id,
            ],
        );

        $this->seedQrCode('NATIONAL', 'QR national FMDC', [
            'support' => 'Affiche nationale',
        ]);

        $this->seedQrCode('ETABLISSEMENT', 'Supermarché Al Manar — Maârif', [
            'etablissement' => 'Supermarché Al Manar — Maârif',
            'secteur' => 'commerce',
            'region' => 'casablanca_settat',
            'support' => 'Autocollant caisse',
        ]);

        $this->command->info('Seed terminé. Connexion : admin@consommateurs.ma / Fmdc2026!');
        foreach (QrCode::all() as $qr) {
            $this->command->info("  QR {$qr->type} : /r/{$qr->code} ({$qr->libelle})");
        }
    }

    private function seedQrCode(string $type, string $libelle, array $extra = []): void
    {
        if (QrCode::where('libelle', $libelle)->exists()) {
            return;
        }

        $code = Signer::randomSlug();

        QrCode::create([
            ...$extra,
            'code' => $code,
            'type' => $type,
            'libelle' => $libelle,
            'signature' => Signer::hmac($code),
        ]);
    }
}
