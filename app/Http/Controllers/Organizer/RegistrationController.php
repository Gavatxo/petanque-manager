<?php

namespace App\Http\Controllers\Organizer;

use App\Application\Tournament\ConvertRegistrationsToTeams;
use App\Enums\RegistrationStatus;
use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\RegistrationPlayer;
use App\Models\Tournament;
use App\Services\QrCodeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
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
