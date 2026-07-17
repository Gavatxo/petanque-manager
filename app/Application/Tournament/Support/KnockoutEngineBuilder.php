<?php

declare(strict_types=1);

namespace App\Application\Tournament\Support;

use App\Domain\Tournament\Enum\Division;
use App\Domain\Tournament\Knockout\KnockoutEngine;
use App\Domain\Tournament\Knockout\KnockoutEntrant;
use App\Domain\Tournament\ValueObject\TeamId;
use App\Models\Matchup;
use App\Models\Team;
use App\Models\Tournament;

/**
 * Reconstruit le moteur d'un tableau à élimination directe pour une division,
 * en rejouant les résultats déjà saisis.
 */
final class KnockoutEngineBuilder
{
    public function build(Tournament $tournament, string $division): KnockoutEngine
    {
        $entrants = [];

        foreach ($tournament->teams()->where('division', $division)->orderBy('division_seed')->get() as $team) {
            /** @var Team $team */
            $entrants[] = new KnockoutEntrant(
                TeamId::of((string) $team->id),
                $team->name,
                $team->division_seed ?? $team->seed,
            );
        }

        $engine = new KnockoutEngine($entrants, Division::from($division));

        $finished = $tournament->matches()
            ->where('phase', 'knockout')
            ->where('division', $division)
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
