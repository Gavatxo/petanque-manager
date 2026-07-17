<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Résultat obtenu par forfait (l'adversaire ne s'est pas présenté).
            $table->boolean('is_forfeit')->default(false)->after('is_walkover');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn('is_forfeit');
        });
    }
};
