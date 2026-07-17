<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Matchup;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Suivi public en temps réel d'une équipe, accessible via le token de sa demande
 * d'inscription. Montre son statut sur le terrain (en jeu, couverte, en attente…).
 */
class TeamStatusController extends Controller
{
    public function show(Registration $registration): Response
    {
        $registration->load('tournament');
        $tournament = $registration->tournament;
        $team = Team::query()->where('registration_id', $registration->id)->first();

        $teamName = $team !== null
            ? $team->name
            : ($registration->team_name ?? 'Votre équipe');

        return Inertia::render('public/team-status', [
            'tournamentId' => $tournament->id,
            'tournamentName' => $tournament->name,
            'club' => $tournament->location,
            'currentPhase' => $tournament->current_phase,
            'registrationStatusLabel' => $registration->status->label(),
            'teamName' => $teamName,
            'team' => $team !== null ? $this->teamStatus($team) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function teamStatus(Team $team): array
    {
        /** @var array<int, string> $names */
        $names = $team->tournament->teams()->pluck('name', 'id')->all();
        /** @var array<int, string> $courts */
        $courts = $team->tournament->courts()->pluck('label', 'id')->all();

        $matches = Matchup::query()
            ->where('tournament_id', $team->tournament_id)
            ->where(fn ($q) => $q->where('team_a_id', $team->id)->orWhere('team_b_id', $team->id))
            ->get();

        $finished = $matches->where('status', 'finished');
        $wins = $finished->where('winner_team_id', $team->id)->count();
        $losses = $finished->whereNotNull('winner_team_id')->where('winner_team_id', '!=', $team->id)->count();

        /** @var Matchup|null $current */
        $current = $matches
            ->whereIn('status', ['playing', 'ready', 'pending'])
            ->sortByDesc('round')
            ->first();

        /** @var Matchup|null $previous */
        $previous = $finished->sortByDesc('result_sequence')->first();

        return [
            'name' => $team->name,
            'wins' => $wins,
            'losses' => $losses,
            'in_progress' => $current !== null && $current->status === 'playing' ? 1 : 0,
            'division' => $team->division,
            'final_rank' => $team->final_rank,
            'round' => [
                'current' => $team->tournament->matches()->where('phase', 'qualification')->max('round') ?? 0,
                'total' => $team->tournament->qualifying_rounds,
            ],
            'live' => $this->liveState($team, $current, $names, $courts),
            'previous' => $previous !== null ? $this->previousResult($team, $previous, $names, $courts) : null,
            'rank' => $this->rank($team, $wins),
        ];
    }

    /**
     * @param  array<int, string>  $names
     * @param  array<int, string>  $courts
     * @return array<string, mixed>
     */
    private function previousResult(Team $team, Matchup $match, array $names, array $courts): array
    {
        $isA = $match->team_a_id === $team->id;
        $opponentId = $isA ? $match->team_b_id : $match->team_a_id;

        return [
            'opponent' => $opponentId !== null ? ($names[$opponentId] ?? null) : null,
            'court' => $match->court_id !== null ? ($courts[$match->court_id] ?? null) : null,
            'my_score' => $isA ? $match->score_a : $match->score_b,
            'their_score' => $isA ? $match->score_b : $match->score_a,
            'won' => $match->winner_team_id === $team->id,
        ];
    }

    /**
     * Position provisoire au nombre de victoires.
     *
     * @return array{position: int, total: int, remaining: int}
     */
    private function rank(Team $team, int $wins): array
    {
        $winsByTeam = Matchup::query()
            ->where('tournament_id', $team->tournament_id)
            ->where('status', 'finished')
            ->whereNotNull('winner_team_id')
            ->get()
            ->groupBy('winner_team_id')
            ->map(fn (Collection $group): int => $group->count());

        $ahead = $team->tournament->teams()->get()
            ->filter(fn (Team $other): bool => (int) $winsByTeam->get($other->id, 0) > $wins)
            ->count();

        $played = (int) $team->tournament->matches()
            ->where('phase', 'qualification')
            ->where(fn ($q) => $q->where('team_a_id', $team->id)->orWhere('team_b_id', $team->id))
            ->where('status', 'finished')
            ->count();

        return [
            'position' => $ahead + 1,
            'total' => $team->tournament->teams()->count(),
            'remaining' => max(0, $team->tournament->qualifying_rounds - $played),
        ];
    }

    /**
     * @param  array<int, string>  $names
     * @param  array<int, string>  $courts
     * @return array{key: string, label: string, opponent: string|null, court: string|null}
     */
    private function liveState(Team $team, ?Matchup $current, array $names, array $courts): array
    {
        if ($team->final_rank !== null) {
            return [
                'key' => 'done',
                'label' => "Terminé — classé {$team->final_rank}"
                    .($team->division !== null ? " (tableau {$team->division})" : ''),
                'opponent' => null,
                'court' => null,
            ];
        }

        if ($current === null) {
            return ['key' => 'waiting', 'label' => 'En attente d’un adversaire', 'opponent' => null, 'court' => null];
        }

        $opponentId = $current->team_a_id === $team->id ? $current->team_b_id : $current->team_a_id;
        $opponent = $opponentId !== null ? ($names[$opponentId] ?? null) : null;
        $court = $current->court_id !== null ? ($courts[$current->court_id] ?? null) : null;

        if ($current->status === 'playing') {
            return ['key' => 'playing', 'label' => 'En jeu', 'opponent' => $opponent, 'court' => $court];
        }

        if ($opponent === null) {
            return ['key' => 'waiting', 'label' => 'En attente d’un adversaire', 'opponent' => null, 'court' => null];
        }

        return ['key' => 'covered', 'label' => 'Couverte — en attente d’un terrain', 'opponent' => $opponent, 'court' => null];
    }
}
