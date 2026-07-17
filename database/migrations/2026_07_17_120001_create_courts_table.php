<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('status')->default('available'); // see CourtStatus enum
            $table->timestamps();

            $table->unique(['tournament_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
