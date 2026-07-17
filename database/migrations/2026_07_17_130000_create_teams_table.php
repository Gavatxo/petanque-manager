<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('seed');                 // ordre d'inscription (déterminisme du moteur)

            // Renseignés à la clôture des qualifications / des finales.
            $table->string('division')->nullable();          // A / B / C / D
            $table->unsignedInteger('division_seed')->nullable(); // classement d'entrée dans le tableau
            $table->unsignedInteger('final_rank')->nullable();    // place finale dans sa division

            $table->timestamps();

            $table->unique(['tournament_id', 'seed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
