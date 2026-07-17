<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Jeton du QR de suivi de l'équipe (non devinable).
            $table->ulid('follow_token')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropUnique(['follow_token']);
            $table->dropColumn('follow_token');
        });
    }
};
