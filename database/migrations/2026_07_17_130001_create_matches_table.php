<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();

            $table->string('phase');                 // qualification | knockout
            $table->string('engine_game_id');        // identifiant de la partie dans le moteur (rejeu déterministe)
            $table->unsignedInteger('round');
            $table->string('division')->nullable();  // knockout uniquement
            $table->unsignedInteger('bracket_index')->nullable();

            $table->foreignId('team_a_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team_b_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('court_id')->nullable()->constrained('courts')->nullOnDelete();

            $table->unsignedSmallInteger('score_a')->nullable();
            $table->unsignedSmallInteger('score_b')->nullable();
            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();

            $table->string('status');                // pending | playing | ready | finished | bye
            $table->boolean('is_walkover')->default(false);
            $table->unsignedInteger('result_sequence')->nullable(); // ordre chronologique de saisie (rejeu)

            $table->timestamps();

            // La division distingue les mêmes ids de partie d'un tableau à l'autre
            // (chaque division a ses propres « k1-0 »…). Les qualifications ont une
            // division nulle et sont protégées des doublons par la couche applicative.
            $table->unique(['tournament_id', 'phase', 'division', 'engine_game_id']);
            $table->index(['tournament_id', 'phase', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
