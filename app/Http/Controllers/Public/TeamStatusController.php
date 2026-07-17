<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Matchup;
use App\Models\Registration;
use App\Models\Team;
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

        $data = [
            'tournamentId' => $tournament->id,
            'tournamentName' => $tournament->name,
            'currentPhase' => $tournament->current_phase,
            'registrationStatusLabel' => $registration->status->label(),
            'teamName' => $teamName,
            'team' => $team !== null ? $this->teamStatus($team) : null,
        ];

        return Inertia::render('public/team-status', $data);
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

        $wins = $matches->where('status', 'finished')->where('winner_team_id', $team->id)->count();
        $losses = $matches->where('status', 'finished')
            ->where('winner_team_id', '!=', $team->id)
            ->whereNotNull('winner_team_id')
            ->count();

        /** @var Matchup|null $current */
        $current = $matches
            ->whereIn('status', ['playing', 'ready', 'pending'])
            ->sortByDesc('round')
            ->first();

        return [
            'name' => $team->name,
            'wins' => $wins,
            'losses' => $losses,
            'division' => $team->division,
            'final_rank' => $team->final_rank,
            'live' => $this->liveState($team, $current, $names, $courts),
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
