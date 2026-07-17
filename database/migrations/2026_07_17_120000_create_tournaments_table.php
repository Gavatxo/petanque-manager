<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('scheduled_at')->nullable();

            // Format configuration
            $table->string('team_format')->default('doublette');       // tete_a_tete | doublette | triplette
            $table->unsignedTinyInteger('qualifying_rounds')->default(3);
            $table->unsignedTinyInteger('tableaux_count')->default(1);  // divisions A/B/C/D
            $table->unsignedTinyInteger('points_target')->default(13);
            $table->unsignedInteger('max_teams')->nullable();

            $table->string('status')->default('draft')->index();        // see TournamentStatus enum
            $table->ulid('registration_token')->unique();               // encoded in the inscription QR code
            $table->json('settings')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
