<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Application\Tournament\Exception\TournamentWorkflowException;
use App\Application\Tournament\Support\KnockoutEngineBuilder;
use App\Application\Tournament\Support\MatchSynchronizer;
use App\Application\Tournament\Support\SwissEngineBuilder;
use App\Enums\TournamentStatus;
use App\Events\TournamentUpdated;
use App\Models\Matchup;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Saisit le résultat d'une partie (qualification ou phase finale).
 *
 * Le moteur adéquat est reconstruit à partir de l'état persisté, le résultat lui
 * est appliqué, puis les conséquences (avancement de ronde / propagation dans le
 * tableau, classements) sont persistées. Aucun calcul d'appariement côté Eloquent.
 */
final class RecordMatchResult
{
    public function __construct(
        private readonly SwissEngineBuilder $swissBuilder,
        private readonly KnockoutEngineBuilder $knockoutBuilder,
        private readonly MatchSynchronizer $synchronizer,
        private readonly GenerateNextQualificationMatches $generateNext,
    ) {}

    public function handle(Matchup $match, int $scoreA, int $scoreB): void
    {
        $tournamentId = $match->tournament_id;

        DB::transaction(function () use ($match, $scoreA, $scoreB): void {
            /** @var Tournament $tournament */
            $tournament = Tournament::query()->whereKey($match->tournament_id)->lockForUpdate()->firstOrFail();
            $match->refresh();

            if ($match->phase === 'qualification') {
                $this->recordQualification($tournament, $match, $scoreA, $scoreB);

                return;
            }

            $this->recordKnockout($tournament, $match, $scoreA, $scoreB);
        });

        // Diffuse le changement une fois la transaction validée.
        TournamentUpdated::dispatch($tournamentId);
    }

    private function recordQualification(Tournament $tournament, Matchup $match, int $scoreA, int $scoreB): void
    {
        if ($match->status !== 'playing') {
            throw TournamentWorkflowException::because("La partie {$match->id} n'est pas en cours.");
        }

        $engine = $this->swissBuilder->build($tournament);
        $engine->recordResult($match->engine_game_id, $scoreA, $scoreB);

        $match->update([
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'winner_team_id' => $scoreA > $scoreB ? $match->team_a_id : $match->team_b_id,
            'status' => 'finished',
            // On conserve court_id (historique : « Terrain N » sur les parties terminées).
            'result_sequence' => $this->nextSequence($tournament),
        ]);

        // Le moteur, rejoué avec ce résultat, matérialise la ronde suivante et
        // réaffecte les terrains libérés.
        $this->generateNext->handle($tournament->fresh());
    }

    private function recordKnockout(Tournament $tournament, Matchup $match, int $scoreA, int $scoreB): void
    {
        if ($match->status !== 'ready') {
            throw TournamentWorkflowException::because("La partie {$match->id} n'est pas prête à être jouée.");
        }

        $division = (string) $match->division;

        $engine = $this->knockoutBuilder->build($tournament, $division);
        $engine->recordResult($match->engine_game_id, $scoreA, $scoreB);

        $match->update([
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'winner_team_id' => $scoreA > $scoreB ? $match->team_a_id : $match->team_b_id,
            'status' => 'finished',
            'result_sequence' => $this->nextSequence($tournament),
        ]);

        // Le moteur a propagé le vainqueur : on réplique le tableau à jour.
        $this->synchronizer->syncKnockout($tournament, $division, $engine);

        if ($engine->isComplete()) {
            foreach ($engine->finalRanking() as $ranked) {
                Team::query()->whereKey((int) $ranked->teamId->value)->update(['final_rank' => $ranked->position]);
            }
        }

        $this->finishTournamentIfDone($tournament);
    }

    private function finishTournamentIfDone(Tournament $tournament): void
    {
        $hasKnockout = $tournament->matches()->where('phase', 'knockout')->exists();
        $stillPlaying = $tournament->matches()
            ->where('phase', 'knockout')
            ->whereIn('status', ['pending', 'ready'])
            ->exists();

        if ($hasKnockout && ! $stillPlaying) {
            $tournament->update([
                'status' => TournamentStatus::Finished,
                'current_phase' => 'completed',
                'finished_at' => now(),
            ]);
        }
    }

    private function nextSequence(Tournament $tournament): int
    {
        return (int) $tournament->matches()->max('result_sequence') + 1;
    }
}
