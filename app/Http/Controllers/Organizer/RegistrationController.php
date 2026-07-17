<?php

namespace App\Http\Controllers\Organizer;

use App\Application\Tournament\ConvertRegistrationsToTeams;
use App\Enums\RegistrationStatus;
use App\Enums\TournamentStatus;
use App\Events\TournamentUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreManualRegistrationRequest;
use App\Models\Registration;
use App\Models\RegistrationPlayer;
use App\Models\Tournament;
use App\Services\QrCodeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    use AuthorizesRequests;

    public function index(Tournament $tournament, QrCodeService $qrCode): Response
    {
        $this->authorize('view', $tournament);

        $tournament->load(['registrations.players', 'registrations.team']);

        return Inertia::render('organizer/tournaments/registrations', [
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status->value,
                'status_label' => $tournament->status->label(),
                'registration_url' => $tournament->registrationUrl(),
                'max_teams' => $tournament->max_teams,
            ],
            'registrationOpen' => $tournament->status === TournamentStatus::RegistrationOpen,
            'teamSize' => $tournament->team_format->teamSize(),
            'registrationQr' => $qrCode->dataUri($tournament->registrationUrl()),
            'registrations' => $tournament->registrations
                ->sortBy('id')
                ->map(fn (Registration $registration) => [
                    'id' => $registration->id,
                    'team_name' => $registration->team_name ?? '(sans nom)',
                    'status' => $registration->status->value,
                    'status_label' => $registration->status->label(),
                    'has_team' => $registration->team !== null,
                    'players' => $registration->players
                        ->map(fn (RegistrationPlayer $player) => [
                            'name' => trim($player->first_name.' '.$player->last_name),
                            'is_captain' => $player->is_captain,
                        ])
                        ->values(),
                ])
                ->values(),
            'teamsCount' => $tournament->teams()->count(),
            'readyToConvert' => $tournament->registrations()
                ->where('status', RegistrationStatus::CheckedIn->value)
                ->whereDoesntHave('team')
                ->count(),
        ]);
    }

    /**
     * Inscription manuelle d'une équipe par l'organisateur (déjà confirmée).
     */
    public function store(StoreManualRegistrationRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        /** @var array{team_name?: string|null, players: list<array<string, mixed>>} $validated */
        $validated = $request->validated();

        DB::transaction(function () use ($tournament, $validated): void {
            $registration = $tournament->registrations()->create([
                'team_name' => ($validated['team_name'] ?? null) ?: null,
                'status' => RegistrationStatus::Confirmed,
                'confirmed_at' => now(),
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
        });

        TournamentUpdated::dispatch($tournament->id);

        return $this->back('Équipe ajoutée.');
    }

    public function confirm(Registration $registration): RedirectResponse
    {
        $this->authorize('update', $registration);

        if ($registration->status === RegistrationStatus::Pending) {
            $registration->update([
                'status' => RegistrationStatus::Confirmed,
                'confirmed_at' => now(),
            ]);
        }

        return $this->back('Inscription confirmée.');
    }

    public function checkIn(Registration $registration): RedirectResponse
    {
        $this->authorize('update', $registration);

        if (in_array($registration->status, [RegistrationStatus::Pending, RegistrationStatus::Confirmed], true)) {
            $registration->update([
                'status' => RegistrationStatus::CheckedIn,
                'confirmed_at' => $registration->confirmed_at ?? now(),
                'checked_in_at' => now(),
            ]);
        }

        return $this->back('Présence validée.');
    }

    public function cancel(Registration $registration): RedirectResponse
    {
        $this->authorize('update', $registration);

        if ($registration->status !== RegistrationStatus::Cancelled && $registration->team === null) {
            $registration->update([
                'status' => RegistrationStatus::Cancelled,
                'cancelled_at' => now(),
            ]);
        }

        return $this->back('Inscription annulée.');
    }

    public function createTeams(Tournament $tournament, ConvertRegistrationsToTeams $convert): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $created = $convert->handle($tournament);

        return $this->back($created.' équipe(s) officielle(s) créée(s).');
    }

    private function back(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
