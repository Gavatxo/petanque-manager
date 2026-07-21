<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Application\Tournament\Exception\TournamentWorkflowException;
use App\Application\Tournament\Support\SwissEngineBuilder;
use App\Domain\Tournament\Configuration\DivisionPlanner;
use App\Models\Matchup;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Clôture les qualifications : vérifie qu'elles sont terminées, classe les
 * équipes (victoires, puis goal average) et les répartit dans les tableaux
 * selon les tailles choisies — les tableaux du haut à taille fixe (idéalement
 * puissances de 2), le dernier absorbant le reste. Le goal average peut repêcher
 * une équipe dans un tableau supérieur.
 */
final class CompleteQualification
{
    public function __construct(
        private readonly SwissEngineBuilder $engineBuilder,
    ) {}

    public function handle(Tournament $tournament): void
    {
        $engine = $this->engineBuilder->build($tournament);

        if (! $engine->isCompleted()) {
            throw TournamentWorkflowException::because('Les qualifications ne sont pas terminées.');
        }

        // Victoires depuis le moteur (exempts inclus).
        $wins = [];
        foreach ($engine->teams() as $team) {
            $wins[(int) $team->id->value] = $team->wins();
        }

        // Goal average (points marqués − encaissés) depuis les parties jouées.
        $goalAverage = $this->goalAverages($tournament);

        // Classement : victoires desc, puis goal average desc, puis seed.
        $ranked = $tournament->teams()->get()
            ->sort(function (Team $a, Team $b) use ($wins, $goalAverage): int {
                return ($wins[$b->id] ?? 0) <=> ($wins[$a->id] ?? 0)
                    ?: ($goalAverage[$b->id] ?? 0) <=> ($goalAverage[$a->id] ?? 0)
                    ?: $a->seed <=> $b->seed;
            })
            ->values();

        /** @var list<int> $rankedIds */
        $rankedIds = $ranked->pluck('id')->all();

        $tableauxCount = $tournament->tableaux_count;
        /** @var list<int> $upperSizes */
        $upperSizes = $tournament->division_sizes
            ?? DivisionPlanner::suggestUpperSizes(count($rankedIds), $tableauxCount);

        $assignment = DivisionPlanner::plan($rankedIds, $upperSizes, $tableauxCount);

        DB::transaction(function () use ($assignment): void {
            foreach ($assignment as $teamId => $slot) {
                Team::query()->whereKey($teamId)->update([
                    'division' => $slot['division'],
                    'division_seed' => $slot['seed'],
                ]);
            }
        });
    }

    /**
     * Différence de points (marqués − encaissés) par équipe sur les parties de
     * qualification terminées. Les exempts ne comptent pas (aucun point).
     *
     * @return array<int, int> teamId => goal average
     */
    private function goalAverages(Tournament $tournament): array
    {
        $goalAverage = [];

        $matches = $tournament->matches()
            ->where('phase', 'qualification')
            ->where('status', 'finished')
            ->get();

        foreach ($matches as $match) {
            /** @var Matchup $match */
            $scoreA = (int) $match->score_a;
            $scoreB = (int) $match->score_b;

            if ($match->team_a_id !== null) {
                $goalAverage[$match->team_a_id] = ($goalAverage[$match->team_a_id] ?? 0) + ($scoreA - $scoreB);
            }

            if ($match->team_b_id !== null) {
                $goalAverage[$match->team_b_id] = ($goalAverage[$match->team_b_id] ?? 0) + ($scoreB - $scoreA);
            }
        }

        return $goalAverage;
    }
}
