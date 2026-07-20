<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Numéro d'équipe attribué à la validation de la présence (1, 2, 3…), dans
 * l'ordre. Il sert de repère pour le tirage et devient le `seed` de l'équipe
 * officielle à la conversion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable()->after('team_name');
        });

        // Reprise des données existantes : les inscriptions déjà « présence
        // validée » reçoivent un numéro. On respecte le seed de l'équipe déjà
        // créée, puis on complète séquentiellement pour les autres.
        foreach (DB::table('registrations')->distinct()->pluck('tournament_id') as $tournamentId) {
            $rows = DB::table('registrations')
                ->leftJoin('teams', 'teams.registration_id', '=', 'registrations.id')
                ->where('registrations.tournament_id', $tournamentId)
                ->where('registrations.status', 'checked_in')
                ->orderByRaw('COALESCE(teams.seed, 999999)')
                ->orderBy('registrations.checked_in_at')
                ->orderBy('registrations.id')
                ->select('registrations.id', 'teams.seed')
                ->get();

            $next = 0;
            foreach ($rows as $row) {
                if ($row->seed !== null) {
                    $number = (int) $row->seed;
                    $next = max($next, $number);
                } else {
                    $number = ++$next;
                }

                DB::table('registrations')->where('id', $row->id)->update(['number' => $number]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
