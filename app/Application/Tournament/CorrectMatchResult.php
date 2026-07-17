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
 * Corrige le score d'une partie déjà terminée (un bénévole peut se tromper).
 *
 *  - Si le vainqueur ne change pas (ex. 13-8 → 13-10) : simple mise à jour.
 *  - Si le vainqueur change : le concours est recalculé à partir de cette
 *    partie (les tours/parties en aval, désormais invalides, sont régénérés).
 */
final class CorrectMatchResult
{
    public function __construct(
        private readonly SwissEngineBuilder $swissBuilder,
        private readonly KnockoutEngineBuilder $knockoutBuilder,
        private readonly MatchSynchronizer $synchronizer,
    ) {}

    /**
     * @return array{recalculated: bool}
     */
    public function handle(Matchup $match, int $scoreA, int $scoreB): array
    {
        if ($match->status !== 'finished' || $match->is_walkover) {
            throw TournamentWorkflowException::because('Seule une partie terminée peut être corrigée.');
        }

        $tournament = $match->tournament;
        $this->assertValidScore($tournament->points_target, $scoreA, $scoreB);

        $newWinnerId = $scoreA > $scoreB ? $match->team_a_id : $match->team_b_id;

        if ($newWinnerId === $match->winner_team_id) {
            $match->update(['score_a' => $scoreA, 'score_b' => $scoreB]);
            TournamentUpdated::dispatch($tournament->id);

            return ['recalculated' => false];
        }

        if ($match->phase === 'qualification') {
            $this->recalculateQualification($tournament, $match, $scoreA, $scoreB, (int) $newWinnerId);
        } else {
            $this->recalculateKnockout($tournament, $match, $scoreA, $scoreB, (int) $newWinnerId);
        }

        TournamentUpdated::dispatch($tournament->id);

        return ['recalculated' => true];
    }

    /**
     * Vainqueur inversé en qualification : les tours suivants et toutes les
     * finales deviennent invalides ; on les efface et on rejoue le moteur.
     */
    private function recalculateQualification(Tournament $tournament, Matchup $match, int $scoreA, int $scoreB, int $newWinnerId): void
    {
        DB::transaction(function () use ($tournament, $match, $scoreA, $scoreB, $newWinnerId): void {
            $round = $match->round;

            $match->update([
                'score_a' => $scoreA,
                'score_b' => $scoreB,
                'winner_team_id' => $newWinnerId,
            ]);

            $tournament->matches()->where('phase', 'qualification')->where('round', '>', $round)->delete();
            $tournament->matches()->where('phase', 'knockout')->delete();
            $tournament->teams()->update(['division' => null, 'division_seed' => null, 'final_rank' => null]);
            $tournament->update([
                'current_phase' => 'qualification',
                'status' => TournamentStatus::Running,
                'finished_at' => null,
            ]);

            $fresh = $tournament->fresh();
            $engine = $this->swissBuilder->build($fresh);
            $this->synchronizer->syncQualification($fresh, $engine);
        });
    }

    /**
     * Vainqueur inversé en phase finale : la propagation change ; on efface les
     * tours suivants de ce tableau et on rejoue la division.
     */
    private function recalculateKnockout(Tournament $tournament, Matchup $match, int $scoreA, int $scoreB, int $newWinnerId): void
    {
        DB::transaction(function () use ($tournament, $match, $scoreA, $scoreB, $newWinnerId): void {
            $division = (string) $match->division;
            $round = $match->round;

            $match->update([
                'score_a' => $scoreA,
                'score_b' => $scoreB,
                'winner_team_id' => $newWinnerId,
            ]);

            $tournament->matches()
                ->where('phase', 'knockout')->where('division', $division)->where('round', '>', $round)
                ->delete();
            $tournament->teams()->where('division', $division)->update(['final_rank' => null]);
            $tournament->update([
                'current_phase' => 'finals',
                'status' => TournamentStatus::Running,
                'finished_at' => null,
            ]);

            $fresh = $tournament->fresh();
            $engine = $this->knockoutBuilder->build($fresh, $division);
            $this->synchronizer->syncKnockout($fresh, $division, $engine);

            if ($engine->isComplete()) {
                foreach ($engine->finalRanking() as $ranked) {
                    Team::query()->whereKey((int) $ranked->teamId->value)->update(['final_rank' => $ranked->position]);
                }
            }

            $this->finishIfAllDivisionsComplete($fresh);
        });
    }

    private function finishIfAllDivisionsComplete(Tournament $tournament): void
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

    private function assertValidScore(int $target, int $scoreA, int $scoreB): void
    {
        if ($scoreA === $scoreB) {
            throw TournamentWorkflowException::because('Une partie ne peut se terminer sur une égalité.');
        }

        if ($scoreA < 0 || $scoreB < 0) {
            throw TournamentWorkflowException::because('Les scores ne peuvent pas être négatifs.');
        }

        if (max($scoreA, $scoreB) !== $target) {
            throw TournamentWorkflowException::because("Le vainqueur doit atteindre {$target} points.");
        }
    }
}
