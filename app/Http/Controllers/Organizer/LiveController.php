<?php

namespace App\Http\Controllers\Organizer;

use App\Application\Tournament\CorrectMatchResult;
use App\Application\Tournament\Exception\TournamentWorkflowException;
use App\Application\Tournament\ForfeitMatch;
use App\Application\Tournament\RecordMatchResult;
use App\Application\Tournament\StartFinals;
use App\Application\Tournament\StartQualification;
use App\Application\Tournament\Support\KnockoutEngineBuilder;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Matchup;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pilotage du déroulé d'un concours : lancement des qualifications, saisie des
 * résultats, bascule en phases finales, classement. S'appuie exclusivement sur
 * les services applicatifs (aucune logique de moteur ici).
 */
class LiveController extends Controller
{
    use AuthorizesRequests;

    public function show(Tournament $tournament, KnockoutEngineBuilder $knockoutBuilder): Response
    {
        $this->authorize('view', $tournament);

        $tournament->load(['teams', 'courts', 'matches']);

        /** @var array<int, array{name: string, seed: int}> $names */
        $names = $tournament->teams
            ->mapWithKeys(fn (Team $team) => [$team->id => ['name' => $team->name, 'seed' => $team->seed]])
            ->all();

        /** @var array<int, string> $courts */
        $courts = $tournament->courts->mapWithKeys(fn (Court $court) => [$court->id => $court->label])->all();

        $qualificationMatches = $tournament->matches->where('phase', 'qualification');
        $knockoutMatches = $tournament->matches->where('phase', 'knockout');

        $phase = $tournament->current_phase;

        return Inertia::render('organizer/tournaments/live', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status_label' => $tournament->status->label(),
                'current_phase' => $phase,
                'points_target' => $tournament->points_target,
                'team_format_label' => $tournament->team_format->label(),
                'qualifying_rounds' => $tournament->qualifying_rounds,
                'tableaux_count' => $tournament->tableaux_count,
            ],
            'counts' => [
                'teams' => $tournament->teams->count(),
                'courts' => $tournament->courts->count(),
            ],
            'canStartQualification' => $phase === null
                && $tournament->teams->count() >= 2,
            'qualification' => $phase === null ? null : [
                'currentRound' => $tournament->matches->where('phase', 'qualification')->max('round') ?? 0,
                'complete' => $qualificationMatches->isNotEmpty()
                    && $qualificationMatches->whereIn('status', ['pending', 'playing'])->isEmpty(),
                'rounds' => $this->groupByRound($qualificationMatches, $names, $courts),
                'standings' => $this->standings($tournament, $names),
            ],
            'finals' => in_array($phase, ['finals', 'completed'], true)
                ? $this->finals($tournament, $knockoutMatches, $names, $knockoutBuilder)
                : null,
        ]);
    }

    public function startQualification(Tournament $tournament, StartQualification $action): RedirectResponse
    {
        $this->authorize('update', $tournament);

        try {
            $action->handle($tournament);
        } catch (TournamentWorkflowException $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('Qualifications lancées.');
    }

    public function recordResult(Request $request, Matchup $matchup, RecordMatchResult $action): RedirectResponse
    {
        $tournament = $matchup->tournament;
        $this->authorize('update', $tournament);

        $validated = $request->validate([
            'score_a' => ['required', 'integer', 'min:0', 'max:'.$tournament->points_target],
            'score_b' => ['required', 'integer', 'min:0', 'max:'.$tournament->points_target],
        ]);

        try {
            $action->handle($matchup, $validated['score_a'], $validated['score_b']);
        } catch (TournamentWorkflowException|InvalidTournamentStateException $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('Résultat enregistré.');
    }

    public function correctResult(Request $request, Matchup $matchup, CorrectMatchResult $action): RedirectResponse
    {
        $tournament = $matchup->tournament;
        $this->authorize('update', $tournament);

        $validated = $request->validate([
            'score_a' => ['required', 'integer', 'min:0', 'max:'.$tournament->points_target],
            'score_b' => ['required', 'integer', 'min:0', 'max:'.$tournament->points_target],
        ]);

        try {
            $result = $action->handle($matchup, $validated['score_a'], $validated['score_b']);
        } catch (TournamentWorkflowException|InvalidTournamentStateException $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(
            $result['recalculated']
                ? 'Résultat corrigé — le concours a été recalculé.'
                : 'Résultat corrigé.',
        );
    }

    public function startFinals(Tournament $tournament, StartFinals $action): RedirectResponse
    {
        $this->authorize('update', $tournament);

        try {
            $action->handle($tournament);
        } catch (TournamentWorkflowException $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('Phases finales lancées.');
    }

    public function forfeit(Request $request, Matchup $matchup, ForfeitMatch $action): RedirectResponse
    {
        $this->authorize('update', $matchup->tournament);

        $validated = $request->validate([
            'forfeiting_team_id' => ['required', 'integer'],
        ]);

        try {
            $action->handle($matchup, $validated['forfeiting_team_id']);
        } catch (TournamentWorkflowException|InvalidTournamentStateException $e) {
            return $this->error($e->getMessage());
        }

        return $this->success('Forfait enregistré.');
    }

    /**
     * @param  Collection<int, Matchup>  $matches
     * @param  array<int, array{name: string, seed: int}>  $names
     * @param  array<int, string>  $courts
     * @return list<array<string, mixed>>
     */
    private function groupByRound($matches, array $names, array $courts): array
    {
        return array_values(
            $matches
                ->groupBy('round')
                ->map(fn ($group, $round) => [
                    'round' => (int) $round,
                    'matches' => $group
                        ->sortBy('id')
                        ->map(fn (Matchup $m) => $this->matchArray($m, $names, $courts))
                        ->values()
                        ->all(),
                ])
                ->sortBy('round')
                ->all(),
        );
    }

    /**
     * @param  array<int, array{name: string, seed: int}>  $names
     * @param  array<int, string>  $courts
     * @return array<string, mixed>
     */
    private function matchArray(Matchup $m, array $names, array $courts): array
    {
        return [
            'id' => $m->id,
            'round' => $m->round,
            'team_a' => $m->team_a_id !== null ? ($names[$m->team_a_id]['name'] ?? null) : null,
            'team_b' => $m->team_b_id !== null ? ($names[$m->team_b_id]['name'] ?? null) : null,
            'team_a_number' => $m->team_a_id !== null ? ($names[$m->team_a_id]['seed'] ?? null) : null,
            'team_b_number' => $m->team_b_id !== null ? ($names[$m->team_b_id]['seed'] ?? null) : null,
            'court' => $m->court_id !== null ? ($courts[$m->court_id] ?? null) : null,
            'score_a' => $m->score_a,
            'score_b' => $m->score_b,
            'winner_team_id' => $m->winner_team_id,
            'team_a_id' => $m->team_a_id,
            'team_b_id' => $m->team_b_id,
            'status' => $m->status,
            'is_walkover' => $m->is_walkover,
            'is_forfeit' => $m->is_forfeit,
        ];
    }

    /**
     * @param  array<int, array{name: string, seed: int}>  $names
     * @return list<array<string, mixed>>
     */
    private function standings(Tournament $tournament, array $names): array
    {
        $wins = [];
        $losses = [];

        foreach ($tournament->matches->where('phase', 'qualification')->where('status', 'finished') as $m) {
            /** @var Matchup $m */
            $winnerId = $m->winner_team_id;
            $loserId = $winnerId === $m->team_a_id ? $m->team_b_id : $m->team_a_id;
            $wins[$winnerId] = ($wins[$winnerId] ?? 0) + 1;
            $losses[$loserId] = ($losses[$loserId] ?? 0) + 1;
        }

        $standings = [];
        foreach ($names as $teamId => $meta) {
            $standings[] = [
                'team' => $meta['name'],
                'seed' => $meta['seed'],
                'wins' => $wins[$teamId] ?? 0,
                'losses' => $losses[$teamId] ?? 0,
            ];
        }

        usort(
            $standings,
            fn (array $a, array $b): int => $b['wins'] <=> $a['wins']
                ?: $a['losses'] <=> $b['losses']
                ?: $a['seed'] <=> $b['seed'],
        );

        return $standings;
    }

    /**
     * @param  Collection<int, Matchup>  $knockoutMatches
     * @param  array<int, array{name: string, seed: int}>  $names
     * @return list<array<string, mixed>>
     */
    private function finals(Tournament $tournament, $knockoutMatches, array $names, KnockoutEngineBuilder $builder): array
    {
        $courts = [];
        $divisions = [];

        foreach ($tournament->teams->whereNotNull('division')->groupBy('division') as $division => $teams) {
            $division = (string) $division;
            $divisionMatches = $knockoutMatches->where('division', $division);

            $labels = [];
            if ($divisionMatches->isNotEmpty()) {
                $engine = $builder->build($tournament, $division);
                for ($round = 1; $round <= $engine->totalRounds(); $round++) {
                    $labels[$round] = $engine->roundLabel($round);
                }
            }

            $rounds = $divisionMatches
                ->groupBy('round')
                ->map(fn ($group, $round) => [
                    'round' => (int) $round,
                    'label' => $labels[(int) $round] ?? "Tour {$round}",
                    'matches' => $group
                        ->sortBy('bracket_index')
                        ->map(fn (Matchup $m) => $this->matchArray($m, $names, $courts))
                        ->values()
                        ->all(),
                ])
                ->sortBy('round')
                ->values()
                ->all();

            $ranking = $teams
                ->sortBy(fn (Team $team) => $team->final_rank ?? PHP_INT_MAX)
                ->map(fn (Team $team) => [
                    'position' => $team->final_rank,
                    'team' => $team->name,
                ])
                ->values()
                ->all();

            $divisions[] = [
                'label' => $division,
                'rounds' => $rounds,
                'ranking' => $ranking,
                'complete' => $teams->whereNull('final_rank')->isEmpty(),
            ];
        }

        return $divisions;
    }

    private function success(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    private function error(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return back();
    }
}
