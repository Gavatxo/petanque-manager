<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tailles des tableaux du haut choisies par l'organisateur (ex. [8, 8] pour A et
 * B ; le dernier tableau prend le reste). null = suggestion automatique en
 * puissances de 2 selon le nombre d'équipes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->json('division_sizes')->nullable()->after('tableaux_count');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('division_sizes');
        });
    }
};
