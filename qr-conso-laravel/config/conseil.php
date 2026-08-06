<?php

/*
 * Base de connaissance du conseil (§7 : « réponse pédagogique appuyée sur le
 * Guide et les fiches juridiques de la FMDC »).
 *
 * ————————————————————————————————————————————————————————————————————————
 * À VALIDER PAR LA FMDC AVANT TOUTE MISE EN LIGNE.
 *
 * Ces fiches sont un point de départ rédigé à partir de la loi 31-08 édictant
 * des mesures de protection du consommateur (dahir n° 1.11.03 du 18 février
 * 2011). Elles doivent être relues, corrigées et complétées par le service
 * juridique de la Fédération, puis remplacées par le contenu réel du Guide.
 * ————————————————————————————————————————————————————————————————————————
 *
 * Le modèle ne répond QU'À PARTIR de ces fiches. Il n'a pas le droit d'inventer
 * un délai, un article ou une procédure : hors de ce corpus, il doit dire qu'il
 * ne sait pas et orienter vers la FMDC. C'est ce qui empêche un modèle local de
 * produire du faux droit devant un consommateur.
 */
return [

    'fiches' => [

        [
            'id' => 'achat_internet',
            'titre' => 'Achat à distance et commerce en ligne',
            'motscles' => ['internet', 'en ligne', 'ligne', 'site', 'commande', 'colis', 'livraison',
                'distance', 'e-commerce', 'ecommerce', 'retracter', 'rétracter', 'rétractation',
                'retractation', 'annuler', 'renvoyer', 'retour', 'rembourser', 'remboursement',
                // arabe (§10) : sans ces mots-clés, une question en arabe ne recouvrirait
                // aucune fiche et tomberait à tort dans le repli « hors corpus ».
                'الإنترنت', 'انترنت', 'أونلاين', 'موقع', 'طلبية', 'الطلب', 'توصيل',
                'إلغاء', 'استرجاع', 'تراجع', 'إرجاع', 'استرداد'],
            'contenu' => <<<'TXT'
            Pour un achat à distance (internet, téléphone, catalogue), le consommateur dispose
            d'un délai de rétractation de sept jours ouvrables à compter de la réception du
            produit. Il n'a pas à justifier sa décision ni à payer de pénalité ; seuls les frais
            de retour peuvent rester à sa charge.

            Le fournisseur doit rembourser la totalité des sommes versées, frais de livraison
            compris, dans les trente jours suivant l'exercice du droit de rétractation.

            Certains produits sont exclus de ce droit : biens personnalisés ou confectionnés
            sur mesure, biens périssables, enregistrements audio ou vidéo descellés, journaux
            et périodiques.

            Le professionnel doit avoir informé le consommateur de ce droit avant la commande.
            TXT,
            'source' => 'Loi 31-08, dispositions sur la vente à distance',
        ],

        [
            'id' => 'garantie',
            'titre' => 'Garantie légale et produit défectueux',
            'motscles' => ['garantie', 'garanti', 'panne', 'defectueux', 'défectueux', 'casse',
                'cassé', 'ne marche plus', 'ne fonctionne pas', 'reparation', 'réparation',
                'echange', 'échange', 'remplacement', 'vice', 'defaut', 'défaut', 'sav',
                'ضمان', 'الضمان', 'عطل', 'معطل', 'خلل', 'عيب', 'إصلاح', 'تبديل', 'استبدال'],
            'contenu' => <<<'TXT'
            La garantie légale s'applique dans tous les cas et ne peut pas être écartée par
            contrat. Un vendeur ne peut pas proposer sa propre garantie commerciale sans
            rappeler clairement que la garantie légale s'applique de toute façon.

            Le vendeur doit garantir le produit contre les vices cachés pendant au moins un an.
            Selon la situation, le consommateur peut demander la réparation, le remplacement ou
            le remboursement du produit.

            Toute clause qui prétend exonérer le vendeur de cette garantie dans un contrat conclu
            avec un consommateur est interdite.

            Un refus de garantie fondé sur le seul fait que le ticket de caisse est illisible ou
            perdu ne suffit pas : la preuve de l'achat peut être apportée par d'autres moyens,
            par exemple un relevé bancaire.
            TXT,
            'source' => 'Loi 31-08, articles 62 et 65',
        ],

        [
            'id' => 'clauses_abusives',
            'titre' => 'Clauses abusives dans un contrat',
            'motscles' => ['contrat', 'clause', 'abusive', 'abusif', 'conditions', 'petits caracteres',
                'petits caractères', 'engagement', 'resiliation', 'résiliation', 'abonnement',
                'عقد', 'العقد', 'بند', 'شرط', 'تعسفي', 'شروط', 'اشتراك', 'فسخ'],
            'contenu' => <<<'TXT'
            Une clause est abusive lorsqu'elle crée, au détriment du consommateur, un déséquilibre
            significatif entre les droits et les obligations des parties au contrat.

            Les clauses abusives contenues dans un contrat conclu entre un fournisseur et un
            consommateur sont nulles : elles sont réputées non écrites, et le reste du contrat
            continue de s'appliquer.

            Le fait d'avoir signé le contrat ne rend pas une clause abusive valable.
            TXT,
            'source' => 'Loi 31-08, article 15',
        ],

        [
            'id' => 'information_prix',
            'titre' => 'Information et prix affiché',
            'motscles' => ['prix', 'affiche', 'affiché', 'etiquette', 'étiquette', 'caisse',
                'different', 'différent', 'promotion', 'solde', 'facture', 'ticket', 'information',
                'ثمن', 'الثمن', 'سعر', 'السعر', 'معلن', 'ملصق', 'فاتورة', 'وصل', 'تخفيض'],
            'contenu' => <<<'TXT'
            Le consommateur a droit à une information complète avant l'achat : caractéristiques
            du produit ou du service, prix, conditions de vente et modalités de paiement.

            Le prix annoncé, affiché ou étiqueté engage le professionnel. Une différence entre le
            prix affiché en rayon et le prix demandé en caisse peut être contestée.

            Le professionnel doit remettre une facture ou un ticket lorsque le consommateur le
            demande.
            TXT,
            'source' => 'Loi 31-08, obligation générale d\'information',
        ],

        [
            'id' => 'publicite',
            'titre' => 'Publicité trompeuse et pratiques commerciales',
            'motscles' => ['publicite', 'publicité', 'pub', 'trompeuse', 'mensongere', 'mensongère',
                'arnaque', 'tromperie', 'promesse', 'annonce', 'faux',
                'إشهار', 'إعلان', 'مضلل', 'كاذب', 'نصب', 'احتيال', 'خداع'],
            'contenu' => <<<'TXT'
            La publicité trompeuse est interdite. Est trompeuse une publicité qui contient des
            indications fausses ou de nature à induire en erreur sur la nature du produit, ses
            qualités, son prix, les conditions de vente ou l'identité du professionnel.

            Une offre annoncée doit correspondre à ce qui est réellement proposé au consommateur.

            Ce type de pratique intéresse la FMDC même sans litige individuel : un signalement
            permet d'identifier les pratiques répétées et d'alerter les autorités compétentes.
            TXT,
            'source' => 'Loi 31-08, dispositions sur la publicité',
        ],

        [
            'id' => 'credit',
            'titre' => 'Crédit à la consommation',
            'motscles' => ['credit', 'crédit', 'pret', 'prêt', 'banque', 'mensualite', 'mensualité',
                'taux', 'interet', 'intérêt', 'financement', 'emprunt',
                'قرض', 'القرض', 'ائتمان', 'بنك', 'البنك', 'فائدة', 'تمويل', 'أقساط'],
            'contenu' => <<<'TXT'
            Avant la conclusion d'un crédit à la consommation, le prêteur doit remettre au
            consommateur une offre préalable écrite mentionnant les conditions du crédit, dont le
            montant, la durée, le taux et le coût total.

            Le consommateur dispose d'un délai de rétractation après l'acceptation de l'offre.

            Les règles sur l'information du consommateur et sur les clauses abusives s'appliquent
            également aux contrats de crédit.

            Pour un litige bancaire précis, la FMDC oriente vers l'association compétente et, si
            nécessaire, vers l'autorité de régulation du secteur.
            TXT,
            'source' => 'Loi 31-08, dispositions sur l\'endettement',
        ],

        [
            'id' => 'demarche',
            'titre' => 'Que faire en cas de litige',
            'motscles' => ['faire', 'demarche', 'démarche', 'plainte', 'reclamation', 'réclamation',
                'porter plainte', 'recours', 'tribunal', 'justice', 'mediation', 'médiation',
                'association', 'aide', 'qui contacter',
                'شكاية', 'شكوى', 'تظلم', 'وساطة', 'محكمة', 'قضاء', 'جمعية', 'أفعل', 'ماذا'],
            'contenu' => <<<'TXT'
            La première étape est d'écrire au professionnel pour demander une solution, en
            conservant une trace écrite de la demande et de la réponse.

            Si cela ne suffit pas, le consommateur peut saisir la FMDC ou une association de
            protection du consommateur affiliée. La Fédération reçoit la demande, l'oriente vers
            l'association compétente et peut proposer une médiation avec le professionnel.

            En cas d'échec de la médiation, le dossier peut être transmis à l'autorité sectorielle
            compétente, ou porté devant la justice.

            Il est utile de rassembler dès le départ les pièces : facture ou ticket, contrat,
            photos, captures d'écran, échanges de messages.
            TXT,
            'source' => 'Guide FMDC — parcours de traitement',
        ],

    ],

    // Nombre de fiches injectées dans le contexte du modèle.
    'max_fiches' => 3,

    // Longueur maximale d'une question, pour éviter qu'un très long texte ne
    // chasse les fiches hors du contexte du modèle.
    'max_question' => 500,
];
