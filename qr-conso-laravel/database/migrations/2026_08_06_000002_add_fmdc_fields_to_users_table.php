<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les comptes du back-office sont les utilisateurs Laravel, enrichis du rôle et
 * de l'association de rattachement. Un agent ne voit que les dossiers de son
 * association ; le bureau national voit tout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['FMDC_ADMIN', 'ASSOCIATION_AGENT'])
                ->default('ASSOCIATION_AGENT')
                ->after('password');
            $table->foreignUlid('association_id')->nullable()->after('role')
                ->constrained('associations')->nullOnDelete();
            $table->boolean('active')->default(true)->after('association_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('association_id');
            $table->dropColumn(['role', 'active']);
        });
    }
};
