<?php

/*
 * L'assistance IA ne s'adresse qu'aux agents du back-office : ces libellés
 * parlent donc du dossier à traiter, pas de « votre » demande.
 */
return [
    'title' => 'Assistance au traitement',
    'suggest' => 'Résumer ce dossier',
    'running' => 'Analyse en cours…',
    'unavailable' => 'L\'assistance IA n\'est pas disponible actuellement. Le dossier reste traitable normalement.',
    'summaryTitle' => 'Résumé du récit',
    'classifiedAs' => 'Classement proposé',
    'missingTitle' => 'Pièces qui manquent au dossier',
    'urgency' => 'Urgence',
    // Le §8 est explicite : l'IA assiste, elle ne décide pas.
    'disclaimer' => 'Proposition générée automatiquement à partir du récit du consommateur. Elle ne modifie ni le dossier, ni son orientation : la qualification et les suites restent de votre ressort.',
    'local' => 'Traitement effectué localement, sur le serveur de la FMDC. Aucune donnée n\'est envoyée à un service externe.',
    'noDescription' => 'Ce dossier ne contient pas de récit à analyser.',
];
