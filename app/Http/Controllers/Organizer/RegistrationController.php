<?php

namespace App\Http\Controllers\Organizer;

use App\Application\Tournament\ConvertRegistrationsToTeams;
use App\Enums\RegistrationStatus;
use App\Enums\TournamentStatus;
use App\Events\TournamentUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreManualRegistrationRequest;
use App\Http\Requests\Organizer\UpdateManualRegistrationRequest;
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
            'started' => $tournament->current_phase !== null,
            'teamSize' => $tournament->team_format->teamSize(),
            'registrationQr' => $qrCode->dataUri($tournament->registrationUrl()),
            'registrations' => $tournament->registrations
                ->sortBy([['number', 'asc'], ['id', 'asc']])
                ->map(fn (Registration $registration) => [
                    'id' => $registration->id,
                    'team_name' => $registration->team_name ?? '(sans nom)',
                    'raw_team_name' => $registration->team_name,
                    'number' => $registration->number,
                    'status' => $registration->status->value,
                    'status_label' => $registration->status->label(),
                    'has_team' => $registration->team !== null,
                    'players' => $registration->players
                        ->map(fn (RegistrationPlayer $player) => [
                            'first_name' => $player->first_name,
                            'last_name' => $player->last_name,
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
     * Inscription manuelle d'une équipe par l'organisateur : présence
     * directement validée et numéro d'équipe attribué.
     */
    public function store(StoreManualRegistrationRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        /** @var array{team_name?: string|null, players: list<array<string, mixed>>} $validated */
        $validated = $request->validated();

        DB::transaction(function () use ($tournament, $validated): void {
            $now = now();
            $registration = $tournament->registrations()->create([
                'team_name' => ($validated['team_name'] ?? null) ?: null,
                'number' => $this->nextNumber($tournament),
                'status' => RegistrationStatus::CheckedIn,
                'confirmed_at' => $now,
                'checked_in_at' => $now,
            ]);

            $this->syncPlayers($registration, $validated['players']);
        });

        TournamentUpdated::dispatch($tournament->id);

        return $this->back('Équipe ajoutée — présence validée.');
    }

    /**
     * Modification d'une inscription saisie (nom, joueurs) avant le départ du
     * concours. Si l'équipe officielle existe déjà, elle est resynchronisée.
     */
    public function update(UpdateManualRegistrationRequest $request, Registration $registration): RedirectResponse
    {
        $this->authorize('update', $registration);

        if ($registration->tournament->current_phase !== null) {
            return $this->error('Le concours a démarré : les équipes ne sont plus modifiables.');
        }

        if ($registration->status === RegistrationStatus::Cancelled) {
            return $this->error('Une inscription annulée ne peut pas être modifiée.');
        }

        /** @var array{team_name?: string|null, players: list<array<string, mixed>>} $validated */
        $validated = $request->validated();
        $teamName = ($validated['team_name'] ?? null) ?: null;

        DB::transaction(function () use ($registration, $validated, $teamName): void {
            $registration->update(['team_name' => $teamName]);
            $registration->players()->delete();
            $this->syncPlayers($registration, $validated['players']);

            $registration->loadMissing('team');
            if ($registration->team !== null) {
                $team = $registration->team;
                $team->update(['name' => $teamName ?: "Équipe {$team->seed}"]);
                $team->players()->delete();
                foreach ($validated['players'] as $index => $player) {
                    $team->players()->create([
                        'first_name' => $player['first_name'],
                        'last_name' => $player['last_name'],
                        'phone' => $player['phone'] ?? null,
                        'license_number' => $player['license_number'] ?? null,
                        'is_captain' => $index === 0,
                    ]);
                }
            }
        });

        TournamentUpdated::dispatch($registration->tournament_id);

        return $this->back('Équipe modifiée.');
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
            // La validation de présence attribue le numéro d'équipe (dans
            // l'ordre) qui servira au tirage.
            DB::transaction(function () use ($registration): void {
                $registration->update([
                    'status' => RegistrationStatus::CheckedIn,
                    'number' => $registration->number ?? $this->nextNumber($registration->tournament),
                    'confirmed_at' => $registration->confirmed_at ?? now(),
                    'checked_in_at' => now(),
                ]);
            });
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

    /**
     * Prochain numéro d'équipe disponible pour ce concours (jamais réutilisé,
     * pour garder des numéros stables même après une annulation).
     */
    private function nextNumber(Tournament $tournament): int
    {
        return (int) $tournament->registrations()->lockForUpdate()->max('number') + 1;
    }

    /**
     * @param  list<array<string, mixed>>  $players
     */
    private function syncPlayers(Registration $registration, array $players): void
    {
        foreach ($players as $index => $player) {
            $registration->players()->create([
                'first_name' => $player['first_name'],
                'last_name' => $player['last_name'],
                'phone' => $player['phone'] ?? null,
                'license_number' => $player['license_number'] ?? null,
                'is_captain' => $index === 0,
            ]);
        }
    }

    private function back(string $message): RedirectResponse
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
