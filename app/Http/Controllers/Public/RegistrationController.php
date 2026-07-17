<?php

namespace App\Http\Controllers\Public;

use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\RegisterTeamRequest;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inscription publique d'une équipe via le QR code d'un concours.
 * Aucune authentification : accès par le token d'inscription.
 */
class RegistrationController extends Controller
{
    public function show(Tournament $tournament): Response
    {
        return Inertia::render('public/register', [
            'tournament' => [
                'name' => $tournament->name,
                'location' => $tournament->location,
                'scheduled_at' => $tournament->scheduled_at?->toIso8601String(),
                'team_format_label' => $tournament->team_format->label(),
            ],
            'teamSize' => $tournament->team_format->teamSize(),
            'registrationOpen' => $tournament->status === TournamentStatus::RegistrationOpen,
            'isFull' => $tournament->max_teams !== null
                && $tournament->teams()->count() >= $tournament->max_teams,
            'registeredCount' => $tournament->teams()->count(),
            'submitUrl' => "/i/{$tournament->registration_token}",
        ]);
    }

    public function store(RegisterTeamRequest $request, Tournament $tournament): RedirectResponse
    {
        /** @var array{team_name?: string|null, players: list<array<string, mixed>>} $validated */
        $validated = $request->validated();

        $team = DB::transaction(function () use ($tournament, $validated): Team {
            $seed = (int) $tournament->teams()->max('seed') + 1;

            $team = $tournament->teams()->create([
                'name' => ($validated['team_name'] ?? null) ?: "Équipe {$seed}",
                'seed' => $seed,
            ]);

            foreach ($validated['players'] as $index => $player) {
                $team->players()->create([
                    'first_name' => $player['first_name'],
                    'last_name' => $player['last_name'],
                    'phone' => $player['phone'] ?? null,
                    'license_number' => $player['license_number'] ?? null,
                    'is_captain' => $index === 0,
                ]);
            }

            return $team;
        });

        return redirect()->route('registration.confirmed', ['team' => $team->follow_token]);
    }

    public function confirmed(Team $team): Response
    {
        $team->load('players', 'tournament');

        return Inertia::render('public/registered', [
            'tournamentName' => $team->tournament->name,
            'team' => [
                'name' => $team->name,
                'players' => $team->players
                    ->map(fn ($player) => [
                        'first_name' => $player->first_name,
                        'last_name' => $player->last_name,
                        'is_captain' => $player->is_captain,
                    ])
                    ->values(),
            ],
        ]);
    }
}
