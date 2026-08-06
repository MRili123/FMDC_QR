# QR Conso Maroc — version Laravel

Mise en œuvre de la note de cadrage FMDC « Système mobile de réclamation du
consommateur par QR code », en **Laravel 12 + Bootstrap 4 (thème StrikingDash) +
MySQL**, avec une couche d'IA **locale** servie par Ollama.

Le consommateur scanne un QR, décrit son problème en langage courant, joint une
preuve et suit son dossier. Le système route vers l'association compétente sans
que l'usager ait à la connaître.

## Prérequis

| | |
|---|---|
| PHP | **8.2+** — `C:\xampp82\php\php.exe` (XAMPP 8.2.12) |
| MySQL / MariaDB | XAMPP (`C:\xampp`), port 3306 |
| Composer | `C:\xampp82\composer\composer.phar` |
| Ollama | facultatif — sans lui l'application fonctionne, sans assistance IA |

Le PHP 8.0 du XAMPP d'origine est trop ancien pour Laravel : une seconde
installation vit dans `C:\xampp82` et ne sert qu'à exécuter PHP. MySQL reste
celui de `C:\xampp`.

## Démarrage

Démarrer **MySQL** depuis le panneau XAMPP, puis créer la base une fois :

```sql
CREATE DATABASE qrconso_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Ensuite :

```bash
C:\xampp82\php\php.exe C:\xampp82\composer\composer.phar install
copy .env.example .env
C:\xampp82\php\php.exe artisan key:generate
C:\xampp82\php\php.exe artisan migrate --seed
C:\xampp82\php\php.exe artisan db:seed --class=DemoSeeder
set PHP_CLI_SERVER_WORKERS=6 && C:\xampp82\php\php.exe artisan serve
```

Application sur http://127.0.0.1:8000.

**`PHP_CLI_SERVER_WORKERS` n'est pas décoratif.** Le serveur de développement de
Laravel est mono-processus : pendant qu'Ollama réfléchit une vingtaine de
secondes pour résumer un dossier, toute l'application serait figée pour tout le
monde, y compris pour les consommateurs en train de déposer. Avec plusieurs
workers, le reste du site continue de répondre.

## Comptes de démonstration

| Compte | Rôle | Périmètre |
|---|---|---|
| `admin@consommateurs.ma` | Bureau national | tous les dossiers |
| `agent.casa@consommateurs.ma` | Agent | Casablanca-Settat uniquement |

Mot de passe : `Fmdc2026!`

## Pages

| | |
|---|---|
| Accueil | `/fr` — `/ar` pour l'arabe (RTL) |
| Dépôt d'une demande | `/fr/reclamation` |
| Suivi public | `/fr/suivi` |
| Scan d'un QR | `/r/{code}` |
| Back-office | `/fr/admin` |

Les codes QR créés par le seed sont affichés à la fin de `artisan db:seed`.

## Ce que fait l'IA, et ce qu'elle ne fait pas

Le modèle local (`qwen2.5:7b`) sert à deux choses, et à rien d'autre.

**1. Résumer un dossier, pour l'agent.** Sur la fiche d'un dossier, un bouton
« Résumer ce dossier » produit un résumé structuré du récit, une proposition de
catégorie et de motif, et la liste des pièces qui manqueraient. La suggestion ne
modifie ni le dossier ni son orientation ; une valeur hors taxonomie est écartée
plutôt que transmise au routage.

Le parcours de dépôt n'en contient aucune trace : le consommateur raconte avec
ses mots, et c'est ce récit brut qui fait foi.

**2. Répondre aux questions de droit, pour le consommateur.** La démarche
« conseil » du §7 ouvre un dialogue adossé aux fiches juridiques de la FMDC
(`config/conseil.php`). Aucun dossier n'est créé, aucune identité demandée.

### Le garde-fou du conseil

Les fiches pertinentes sont sélectionnées par mots-clés et injectées dans le
contexte ; le modèle a l'interdiction d'en sortir. **Et si aucune fiche ne
correspond, le modèle n'est pas appelé du tout** : une réponse fixe invite à
déposer une demande.

Ce verrou est dans le code, pas dans le prompt, et l'essai l'a rendu nécessaire.
Interrogé sur les sanctions pénales, qu'aucune fiche ne couvre, qwen2.5:7b
répondait « la loi ne prévoit pas de sanction spécifique » — une affirmation
fausse sur le droit marocain, énoncée avec aplomb. Renforcer la consigne n'a pas
suffi. Devant un consommateur, c'est le seul vrai risque de la fonctionnalité.

> **`config/conseil.php` est à valider par la FMDC.** Les fiches actuelles sont
> rédigées à partir de la loi 31-08 et servent de point de départ ; elles doivent
> être relues par le service juridique, puis remplacées par le contenu réel du
> Guide. Le mécanisme est prêt, le contenu ne l'est pas.

La réponse est diffusée en flux, jeton par jeton : à une dizaine de jetons par
seconde, attendre la réponse complète donnerait vingt secondes d'écran figé.

Aucune donnée ne quitte la machine : le modèle tourne en local, ce qui sert
directement les exigences du §9.

Les trois services tiennent dans **une seule génération**. En trois appels
séparés le parcours attendait ~47 s, parce qu'Ollama sérialise les requêtes sur
un même modèle et que le coût est dominé par les jetons produits.

Deux services du §8 restent à faire : la **transcription vocale** (darija, arabe,
amazighe) demande un modèle de parole type Whisper, et l'**OCR** des factures
demande un modèle de vision. Ollama seul ne les couvre pas.

Sans Ollama, `AI_ENABLED=false` ou modèle arrêté, les boutons d'assistance
disparaissent et le reste fonctionne à l'identique.

## Points d'attention

**Bootstrap 4.5 n'a pas de support RTL.** Ses utilitaires directionnels
(`ml-*`, `text-left`, `pl-*`) sont physiques et ne se retournent pas avec
`dir="rtl"`, et le thème StrikingDash en dépend. `public/theme/rtl.css` les
réinterprète, et n'est chargé qu'en arabe. Le CSS propre au projet
(`public/theme/fmdc.css`) utilise déjà des propriétés logiques et n'a rien à
corriger.

**MySQL tronque les `String` à 191 caractères par défaut.** Les champs longs
(description, note, adresse, transcription) sont explicitement `text` dans les
migrations. Ne pas retirer ces types.

**Le suivi public exige la référence ET le jeton.** Les références sont
séquentielles, donc devinables : le jeton remis à la soumission est ce qui
autorise réellement la lecture.

**Le routage ne bloque jamais.** Sans règle correspondante, le dossier tombe dans
la file nationale plutôt que d'être rejeté — c'est ce que « aucune mauvaise
porte » signifie concrètement.

## Avant une mise en production

- Générer un `APP_KEY` propre et passer `APP_DEBUG=false`
- Créer un utilisateur MySQL dédié (`root` sans mot de passe convient à XAMPP en
  développement, jamais au-delà)
- Brancher un vrai fournisseur SMS : `SMS_DRIVER=log` écrit le code OTP dans
  `storage/logs/laravel.log` au lieu de l'envoyer
- Ajouter l'analyse antivirus des pièces jointes prévue au §9
- Servir derrière HTTPS avec le domaine court imprimé sous les QR
