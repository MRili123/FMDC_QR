<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schéma métier de « QR Conso Maroc » (note de cadrage FMDC).
 *
 * Deux partis pris structurants :
 *  - l'identité du requérant vit dans une table séparée du contenu du dossier,
 *    conformément à la séparation exigée au §9 ;
 *  - les régions et secteurs d'une association sont des lignes et non une liste
 *    sérialisée, pour rester interrogeables par le moteur de routage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('associations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nom');
            $table->string('contact')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('association_scopes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('association_id')->constrained('associations')->cascadeOnDelete();
            $table->enum('kind', ['REGION', 'SECTEUR']);
            $table->string('value', 100);
            $table->unique(['association_id', 'kind', 'value']);
            $table->index(['kind', 'value']);
        });

        // Règles déterministes du routage « aucune mauvaise porte » (§5).
        Schema::create('routing_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('categorie', 60);
            $table->string('region', 60)->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->foreignUlid('association_id')->constrained('associations')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['categorie', 'region']);
        });

        // Les trois types de QR du §6.1. `code` est opaque : il ne porte aucune
        // donnée personnelle, seulement un identifiant technique signé.
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique();
            $table->enum('type', ['NATIONAL', 'SECTORIEL', 'ETABLISSEMENT']);
            $table->string('signature', 80);
            $table->string('libelle');
            $table->string('secteur', 60)->nullable();
            $table->string('region', 60)->nullable();
            $table->string('etablissement')->nullable();
            $table->string('support')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('scan_count')->default(0);
            $table->unsignedInteger('reported_count')->default(0);
            $table->timestamps();
        });

        // Contenu de la réclamation. Aucune donnée d'identité ici : voir requerants.
        Schema::create('dossiers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference', 30)->unique();
            $table->enum('demarche', ['CONSEIL', 'SIGNALEMENT', 'RECLAMATION']);
            $table->string('categorie', 60);
            $table->string('motif', 60);
            $table->text('description')->nullable();
            $table->string('resultat_attendu', 60)->nullable();
            $table->enum('status', [
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
            ])->default('RECU');
            $table->string('region', 60)->nullable();
            $table->string('etablissement')->nullable();
            $table->string('professionnel')->nullable();
            $table->string('tracking_token_hash', 80);
            $table->timestamp('first_handled_at')->nullable();
            $table->foreignUlid('assigned_association_id')->nullable()->constrained('associations')->nullOnDelete();
            $table->foreignUlid('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('categorie');
            $table->index('created_at');
        });

        // Données d'identité, volontairement séparées (§9). Absent si signalement anonyme.
        Schema::create('requerants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('dossier_id')->unique()->constrained('dossiers')->cascadeOnDelete();
            $table->string('telephone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('nom', 150)->nullable();
            $table->text('adresse')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Nullable : une pièce est téléversée pendant le brouillon, donc avant
            // que le dossier n'existe. `draft_id` fait le lien en attendant.
            $table->foreignUlid('dossier_id')->nullable()->constrained('dossiers')->cascadeOnDelete();
            $table->string('draft_id', 64)->nullable();
            $table->enum('kind', ['TICKET', 'FACTURE', 'PRODUIT', 'CAPTURE', 'AUDIO', 'AUTRE']);
            $table->string('storage_key', 120);
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->enum('scan_status', ['SKIPPED', 'PENDING', 'CLEAN', 'INFECTED'])->default('SKIPPED');
            $table->text('transcription')->nullable();
            $table->timestamps();

            $table->index('draft_id');
        });

        // Journal append-only : alimente la frise publique de suivi et la traçabilité interne.
        Schema::create('dossier_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->text('note')->nullable();
            $table->boolean('public_note')->default(true);
            $table->string('actor_id', 40)->nullable();
            $table->string('actor_label', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('phone', 30);
            $table->string('code_hash', 80);
            $table->timestamp('expires_at');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index('phone');
        });

        // Journalisation des accès exigée au §9.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('actor_id', 40)->nullable();
            $table->string('actor_label', 150)->nullable();
            $table->string('action', 80);
            $table->string('entity_type', 60);
            $table->string('entity_id', 60);
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('otp_challenges');
        Schema::dropIfExists('dossier_events');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('requerants');
        Schema::dropIfExists('dossiers');
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('routing_rules');
        Schema::dropIfExists('association_scopes');
        Schema::dropIfExists('associations');
    }
};
