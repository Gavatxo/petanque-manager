<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\RegistrationStatus;
use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreTournamentRequest;
use App\Http\Requests\Organizer\UpdateTournamentRequest;
use App\Models\Court;
use App\Models\Tournament;
use App\Services\QrCodeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Tournament::class);

        $tournaments = Tournament::ownedBy($request->user())
            ->withCount('courts')
            ->latest()
            ->get()
            ->map(fn (Tournament $tournament) => $this->toListItem($tournament))
            ->values();

        return Inertia::render('organizer/tournaments/index', [
            'tournaments' => $tournaments,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Tournament::class);

        return Inertia::render('organizer/tournaments/create', [
            'formats' => $this->formatOptions(),
            'defaults' => [
                'team_format' => TeamFormat::Doublette->value,
                'qualifying_rounds' => 3,
                'tableaux_count' => 1,
                'points_target' => 13,
            ],
        ]);
    }

    public function store(StoreTournamentRequest $request): RedirectResponse
    {
        $this->authorize('create', Tournament::class);

        $tournament = $request->user()->tournaments()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concours créé.']);

        return redirect()->route('organizer.tournaments.show', $tournament);
    }

    public function show(Tournament $tournament, QrCodeService $qrCode): Response
    {
        $this->authorize('view', $tournament);

        $tournament->load('courts');

        $byStatus = $tournament->registrations()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('organizer/tournaments/show', [
            'tournament' => $this->toDetail($tournament),
            'registrationQr' => $qrCode->dataUri($tournament->registrationUrl()),
            'registrationSummary' => [
                'pending' => (int) $byStatus->get(RegistrationStatus::Pending->value, 0),
                'confirmed' => (int) $byStatus->get(RegistrationStatus::Confirmed->value, 0),
                'checked_in' => (int) $byStatus->get(RegistrationStatus::CheckedIn->value, 0),
                'cancelled' => (int) $byStatus->get(RegistrationStatus::Cancelled->value, 0),
                'teams' => $tournament->teams()->count(),
            ],
        ]);
    }

    public function qr(Tournament $tournament, QrCodeService $qrCode): \Illuminate\Http\Response
    {
        $this->authorize('view', $tournament);

        return response($qrCode->svg($tournament->registrationUrl(), 512), 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="qr-inscription.svg"',
        ]);
    }

    public function edit(Tournament $tournament): Response
    {
        $this->authorize('update', $tournament);

        $tournament->load('courts');

        return Inertia::render('organizer/tournaments/edit', [
            'tournament' => $this->toDetail($tournament),
            'formats' => $this->formatOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $tournament->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concours mis à jour.']);

        return redirect()->route('organizer.tournaments.show', $tournament);
    }

    public function openRegistrations(Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $tournament->update(['status' => TournamentStatus::RegistrationOpen]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inscriptions ouvertes.']);

        return back();
    }

    public function closeRegistrations(Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $tournament->update(['status' => TournamentStatus::CheckIn]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Inscriptions fermées — place à la validation des présents.']);

        return back();
    }

    public function archive(Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $tournament->update([
            'status' => TournamentStatus::Archived,
            'archived_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concours archivé.']);

        return back();
    }

    public function unarchive(Tournament $tournament): RedirectResponse
    {
        $this->authorize('update', $tournament);

        $tournament->update([
            'status' => TournamentStatus::Draft,
            'archived_at' => null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concours restauré.']);

        return back();
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $this->authorize('delete', $tournament);

        $tournament->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Concours supprimé.']);

        return redirect()->route('organizer.tournaments.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function toListItem(Tournament $tournament): array
    {
        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'location' => $tournament->location,
            'scheduled_at' => $tournament->scheduled_at?->toIso8601String(),
            'status' => $tournament->status->value,
            'status_label' => $tournament->status->label(),
            'team_format' => $tournament->team_format->value,
            'team_format_label' => $tournament->team_format->label(),
            'qualifying_rounds' => $tournament->qualifying_rounds,
            'tableaux_count' => $tournament->tableaux_count,
            'points_target' => $tournament->points_target,
            'max_teams' => $tournament->max_teams,
            'courts_count' => $tournament->courts_count ?? $tournament->courts()->count(),
            'is_archived' => $tournament->isArchived(),
            'created_at' => $tournament->created_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toDetail(Tournament $tournament): array
    {
        return array_merge($this->toListItem($tournament), [
            'description' => $tournament->description,
            'registration_token' => $tournament->registration_token,
            'registration_url' => $tournament->registrationUrl(),
            'courts' => $tournament->courts
                ->sortBy('label', SORT_NATURAL)
                ->map(fn (Court $court) => [
                    'id' => $court->id,
                    'label' => $court->label,
                    'status' => $court->status->value,
                    'status_label' => $court->status->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function formatOptions(): array
    {
        return array_map(
            fn (TeamFormat $format) => ['value' => $format->value, 'label' => $format->label()],
            TeamFormat::cases(),
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (TournamentStatus $status) => ['value' => $status->value, 'label' => $status->label()],
            TournamentStatus::organizerSelectable(),
        );
    }
}
