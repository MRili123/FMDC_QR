<?php

/**
 * Les libellés vivent dans les fichiers de langue (fr/ar) : ici on ne garde que
 * les clés stables, qui sont ce qui est écrit en base et exploité en statistiques.
 */
return [

    'categories' => [
        'achat_magasin',
        'achat_internet',
        'telecom',
        'banque_assurance',
        'eau_energie',
        'transport_livraison',
        'sante',
        'logement',
        'education',
        'tourisme_restauration',
        'service_public',
        'autre',
    ],

    'motifs' => [
        'non_recu',
        'defectueux',
        'prix_errone',
        'refus_remboursement',
        'garantie_refusee',
        'mauvaise_qualite',
        'tromperie',
        'securite_menacee',
        'donnees_personnelles',
        'autre',
    ],

    'resultats' => [
        'remboursement',
        'remplacement',
        'livraison',
        'annulation',
        'correction_facture',
        'arret_pratique',
        'conseil_juridique',
        'controle_alerte',
    ],

    'regions' => [
        'tanger_tetouan_al_hoceima',
        'oriental',
        'fes_meknes',
        'rabat_sale_kenitra',
        'beni_mellal_khenifra',
        'casablanca_settat',
        'marrakech_safi',
        'draa_tafilalet',
        'souss_massa',
        'guelmim_oued_noun',
        'laayoune_sakia_el_hamra',
        'dakhla_oued_ed_dahab',
    ],

    'secteurs' => [
        'commerce',
        'telecom',
        'banque_assurance',
        'ecommerce',
        'transport',
        'sante',
        'eau_energie',
        'tourisme',
        'enseignement',
    ],

    // Les cartes du §7 doivent être reconnaissables sans être lues.
    'category_icons' => [
        'achat_magasin' => '🛒',
        'achat_internet' => '📦',
        'telecom' => '📱',
        'banque_assurance' => '🏦',
        'eau_energie' => '💡',
        'transport_livraison' => '🚚',
        'sante' => '🏥',
        'logement' => '🏠',
        'education' => '🎓',
        'tourisme_restauration' => '🍽️',
        'service_public' => '🏛️',
        'autre' => '❓',
    ],

    'statuses' => [
        'RECU',
        'A_VERIFIER',
        'INFOS_DEMANDEES',
        'ORIENTE_ASSOCIATION',
        'TRANSMIS_PROFESSIONNEL',
        'EN_MEDIATION',
        'TRANSMIS_AUTORITE',
        'RESOLU',
        'CLOTURE',
        'SIGNALEMENT_COLLECTIF',
    ],

    // Un dossier dans un de ces états n'attend plus d'action de la FMDC.
    'terminal_statuses' => ['RESOLU', 'CLOTURE'],

    'demarches' => ['CONSEIL', 'SIGNALEMENT', 'RECLAMATION'],

    'attachment_kinds' => ['TICKET', 'FACTURE', 'PRODUIT', 'CAPTURE', 'AUDIO', 'AUTRE'],

];
