<?php

namespace App\Http\Controllers\Public;

use App\Enums\RegistrationStatus;
use App\Enums\TournamentStatus;
use App\Events\TournamentUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\RegisterTeamRequest;
use App\Models\Registration;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inscription publique à un concours via son QR code (token ULID).
 *
 * Aucune authentification et aucun accès à l'administration : le token ne permet
 * que de déposer une demande d'inscription. L'équipe officielle n'est créée
 * qu'après validation par l'organisateur.
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
                && $tournament->registrations()->active()->count() >= $tournament->max_teams,
            'registeredCount' => $tournament->registrations()->active()->count(),
            'submitUrl' => "/i/{$tournament->registration_token}",
        ]);
    }

    public function store(RegisterTeamRequest $request, Tournament $tournament): RedirectResponse
    {
        /** @var array{team_name?: string|null, players: list<array<string, mixed>>} $validated */
        $validated = $request->validated();

        $registration = DB::transaction(function () use ($tournament, $validated): Registration {
            $registration = $tournament->registrations()->create([
                'team_name' => ($validated['team_name'] ?? null) ?: null,
                'status' => RegistrationStatus::Pending,
            ]);

            foreach ($validated['players'] as $index => $player) {
                $registration->players()->create([
                    'first_name' => $player['first_name'],
                    'last_name' => $player['last_name'],
                    'phone' => $player['phone'] ?? null,
                    'license_number' => $player['license_number'] ?? null,
                    'is_captain' => $index === 0,
                ]);
            }

            return $registration;
        });

        TournamentUpdated::dispatch($tournament->id);

        return redirect()->route('registration.confirmed', ['registration' => $registration->follow_token]);
    }

    public function confirmed(Registration $registration): Response
    {
        $registration->load('players', 'tournament');

        return Inertia::render('public/registered', [
            'tournamentName' => $registration->tournament->name,
            'followUrl' => "/suivi/{$registration->follow_token}",
            'registration' => [
                'team_name' => $registration->team_name,
                'status' => $registration->status->value,
                'status_label' => $registration->status->label(),
                'players' => $registration->players
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
