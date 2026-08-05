# QR Conso Maroc — Phase 1 (MVP)

Système mobile de réclamation du consommateur par QR code, pour la **Fédération
Marocaine des Droits du Consommateur (FMDC)**.

Implémente la phase 1 de la note de cadrage « QR Conso Maroc » : parcours public en
PWA, système de QR codes signés, et back-office élémentaire avec routage vers les
associations affiliées.

## Ce qui est couvert

| Note de cadrage | État |
|---|---|
| §6.2 PWA installable, réseau faible, brouillon sauvegardé | ✅ |
| §6.1 Trois types de QR (national, sectoriel, établissement) | ✅ |
| §7 Parcours en 7 écrans, ≤ 5 informations obligatoires | ✅ |
| §7 Distinction conseil / signalement / réclamation | ✅ |
| §5 Routage « aucune mauvaise porte » | ✅ |
| §6.3 Cycle de statuts et back-office | ✅ |
| §9 Anti-quishing, séparation des identités, journalisation | ✅ |
| §10 Arabe et français, RTL, cibles tactiles larges | ✅ |
| §5 Veille : détection des signaux collectifs | ✅ |
| §8 IA (transcription, reformulation, OCR, classification) | ⏳ interfaces neutres |
| §5 WhatsApp comme canal de premier rang | ⏳ non branché |
| §6.3 Portail professionnel (phase 3) | ⏳ hors périmètre MVP |

## Démarrage

Prérequis : **XAMPP** (MySQL / MariaDB). Démarrer MySQL depuis le panneau de
contrôle XAMPP, puis créer la base une fois :

```sql
CREATE DATABASE qrconso CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Ensuite :

```bash
npm install
cp .env.example .env
npx prisma migrate dev
npm run db:seed
npm run dev
```

Application sur http://localhost:3100. La base est visible dans phpMyAdmin
(http://localhost/phpmyadmin), ou via `npm run db:studio`.

`.env.example` suppose les identifiants XAMPP par défaut (`root`, sans mot de
passe). Si vous avez défini un mot de passe :
`DATABASE_URL="mysql://root:MOTDEPASSE@localhost:3306/qrconso"`.

Comptes de démonstration créés par le seed :

| Compte | Rôle | Mot de passe |
|---|---|---|
| `admin@consommateurs.ma` | Bureau national (voit tout) | `Fmdc2026!` |
| `agent.casa@consommateurs.ma` | Agent d'association (périmètre restreint) | `Fmdc2026!` |

`npm run db:seed` affiche aussi les codes QR générés, à ouvrir sur `/r/<code>`.

Pour peupler le tableau de bord avec des dossiers de démonstration :

```bash
npx tsx prisma/demo.ts
```

## Scripts

| Commande | Rôle |
|---|---|
| `npm run dev` | Serveur de développement |
| `npm run build` | Build de production |
| `npm run typecheck` | Vérification TypeScript |
| `npm run db:migrate` | Applique les migrations |
| `npm run db:seed` | Données initiales |
| `npm run db:studio` | Explorateur de base Prisma |
| `npx tsx prisma/resign.ts` | Régénère les signatures QR après rotation d'`APP_SECRET` |

## Architecture

- **Next.js 16** (App Router, Turbopack) — une seule base pour la PWA, l'API et le back-office
- **MySQL / MariaDB + Prisma 7** avec l'adaptateur `@prisma/adapter-mariadb`
- **i18n maison** (`src/i18n/`) — deux locales, messages statiques, RTL par `dir`
- **Session maison** (`src/server/session.ts`) — JWT signé en cookie httpOnly, agent
  relu en base à chaque requête pour qu'une désactivation prenne effet aussitôt

Toutes les routes vivent sous `/[locale]`. `src/proxy.ts` (le `middleware` renommé
de Next 16) négocie la langue, ce qui permet au QR imprimé de pointer vers
`/r/<code>` sans préfixe de langue.

### Choix structurants

**Identité séparée du contenu.** `Dossier` ne contient aucune donnée personnelle ;
celles-ci vivent dans `Requerant`. Les tableaux de bord n'interrogent jamais la
seconde table. Un signalement anonyme n'a tout simplement pas de `Requerant`.

**L'identité ne s'affiche pas toute seule.** Le back-office la masque par défaut ;
l'afficher écrit une entrée `AuditLog`, y compris à chaque rechargement.

**Le QR ne transporte rien.** Il ne porte qu'un identifiant opaque signé en HMAC.
L'établissement, le secteur et la région sont résolus côté serveur — un client ne
peut pas s'attribuer un établissement. Un code inconnu, désactivé ou dont la
signature ne correspond pas affiche l'avertissement anti-quishing, jamais le
formulaire.

**L'OTP n'est pas déclaratif.** Le client ne peut pas prétendre avoir vérifié un
numéro : à la soumission, le serveur cherche lui-même un défi OTP consommé et
récent pour ce numéro.

**Le routage ne bloque jamais.** Sans règle correspondante, le dossier tombe dans la
file nationale plutôt que d'être rejeté — c'est ce que « aucune mauvaise porte »
signifie concrètement.

### Spécificités MySQL

MySQL n'a pas de colonnes tableau : les régions et secteurs couverts par une
association vivent dans `AssociationScope` (une ligne par valeur, avec un
`kind` REGION ou SECTEUR). C'est aussi plus interrogeable qu'un tableau, ce dont
le routage aura besoin s'il s'appuie un jour sur le périmètre déclaré.

MySQL tronque par défaut les colonnes `String` à 191 caractères. Les champs
longs — description d'un dossier, note d'un événement, adresse, transcription —
sont donc explicitement typés `@db.Text` dans le schéma. Ne pas retirer ces
annotations : une description de 600 caractères serait sinon coupée en silence.

### Capacités reportées

`src/server/providers.ts` regroupe les interfaces des briques non branchées (SMS,
transcription, classification, OCR, antivirus). Chacune a une implémentation neutre :
en développement, le prestataire SMS écrit le code OTP dans la console serveur.
Brancher un vrai prestataire ne doit toucher que ce fichier.

## Vérifié

Parcours complets exercés dans le navigateur : réclamation identifiée avec OTP et
pièce jointe, signalement anonyme depuis un QR d'établissement, suivi public avec et
sans jeton, QR valide / inconnu / signature falsifiée, connexion back-office,
transitions de statut remontant jusqu'à la frise publique, cloisonnement d'un agent
d'association (404 sur un dossier hors périmètre), interface arabe en RTL, absence de
débordement horizontal à 375 px, build de production et `tsc --noEmit`.

**Non vérifié :** l'enregistrement vocal (`MediaRecorder`) exige un micro réel ; le
code est en place mais n'a pas été exercé de bout en bout.

## À confirmer avec la FMDC

- La liste réelle des associations affiliées, leurs régions et secteurs — le seed
  utilise des données fictives
- La délégation de `qr.consommateurs.ma` et le contrôle DNS
- La déclaration CNDP et les durées de conservation à configurer
- L'existence d'une API sur la plateforme actuelle, pour l'intégration de phase 2

## Avant la mise en production

- Générer un vrai `APP_SECRET` (`node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"`)
- Créer un utilisateur MySQL dédié — `root` sans mot de passe convient à XAMPP en
  développement, jamais en production
- Remplacer le stockage disque de `src/server/storage.ts` par un stockage objet
- Brancher un prestataire SMS, activer l'analyse antivirus des pièces jointes
- Configurer HTTPS (les cookies de session passent en `secure` hors développement)
