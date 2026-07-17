<?php

declare(strict_types=1);

namespace App\Application\Tournament\Support;

use App\Domain\Tournament\Configuration\TournamentConfiguration;
use App\Domain\Tournament\TournamentEngine;
use App\Models\Court;
use App\Models\Matchup;
use App\Models\Team;
use App\Models\Tournament;

/**
 * Reconstruit le moteur Swiss de qualification à partir de l'état persisté.
 *
 * Le domaine reste pur : on rejoue simplement, dans l'ordre chronologique de
 * saisie, les résultats déjà enregistrés pour amener le moteur à son état
 * courant. Aucune logique d'appariement ne vit côté Eloquent.
 */
final class SwissEngineBuilder
{
    public function build(Tournament $tournament): TournamentEngine
    {
        $configuration = new TournamentConfiguration(
            qualifyingRounds: $tournament->qualifying_rounds,
            divisionCount: $tournament->tableaux_count,
            pointsTarget: $tournament->points_target,
        );

        $engine = new TournamentEngine($configuration);

        foreach ($tournament->teams()->orderBy('seed')->get() as $team) {
            /** @var Team $team */
            $engine->registerTeam((string) $team->id, $team->name, $team->seed);
        }

        foreach ($tournament->courts()->orderBy('id')->get() as $court) {
            /** @var Court $court */
            $engine->addCourt((string) $court->id, $court->label);
        }

        $engine->start();

        $finished = $tournament->matches()
            ->where('phase', 'qualification')
            ->where('status', 'finished')
            ->orderBy('result_sequence')
            ->get();

        foreach ($finished as $match) {
            /** @var Matchup $match */
            $engine->recordResult(
                $match->engine_game_id,
                (int) $match->score_a,
                (int) $match->score_b,
            );
        }

        return $engine;
    }
}
