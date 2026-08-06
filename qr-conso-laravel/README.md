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
secondes, toute l'application est figée pour tout le monde. Avec plusieurs
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

Trois des services du §8 sont branchés sur un modèle local (`qwen2.5:7b`) :
reformulation d'un récit désordonné en résumé structuré, proposition de catégorie
et de motif, détection des pièces justificatives manquantes.

**L'IA propose, elle ne décide pas.** Le consommateur voit le résumé et choisit
de l'utiliser ou de garder son texte ; la classification est une suggestion
affichée à l'agent, jamais appliquée automatiquement au routage. Une valeur
proposée hors taxonomie est écartée plutôt que transmise.

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
