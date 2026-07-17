<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Enums\TournamentStatus;
use App\Models\Matchup;
use App\Models\Registration;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** @var list<string> */
    private const MONTHS_SHORT = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    /** @var list<string> */
    private const MONTHS_LONG = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        /** @var list<int> $tournamentIds */
        $tournamentIds = $user->tournaments()->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        $checkedIn = Registration::whereIn('tournament_id', $tournamentIds)
            ->where('status', RegistrationStatus::CheckedIn->value)->count();
        $active = Registration::whereIn('tournament_id', $tournamentIds)
            ->where('status', '!=', RegistrationStatus::Cancelled->value)->count();

        return Inertia::render('dashboard', [
            'stats' => [
                'organized' => count($tournamentIds),
                'teams' => Team::whereIn('tournament_id', $tournamentIds)->count(),
                'games' => Matchup::whereIn('tournament_id', $tournamentIds)->where('status', 'finished')->count(),
                'attendance' => $active > 0 ? (int) round(100 * $checkedIn / $active) : null,
            ],
            'chart' => $this->chart($tournamentIds),
            'upcoming' => $this->upcoming($user),
            'recent' => $this->recent($user),
        ]);
    }

    /**
     * @param  list<int>  $tournamentIds
     * @return list<array{label: string, count: int}>
     */
    private function chart(array $tournamentIds): array
    {
        $since = Carbon::now()->startOfMonth()->subMonths(5);

        $counts = Tournament::whereIn('id', $tournamentIds)
            ->where('created_at', '>=', $since)
            ->get(['created_at'])
            ->groupBy(fn (Tournament $t): string => $t->created_at?->format('Y-m') ?? '')
            ->map(fn ($group): int => $group->count());

        $bars = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonths($i);
            $bars[] = [
                'label' => self::MONTHS_SHORT[$month->month - 1][0],
                'count' => (int) ($counts->get($month->format('Y-m')) ?? 0),
            ];
        }

        return $bars;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcoming(User $user): array
    {
        return array_values($user->tournaments()
            ->whereNotIn('status', [TournamentStatus::Finished->value, TournamentStatus::Archived->value])
            ->withCount('teams')
            ->orderByRaw('scheduled_at is null, scheduled_at asc')
            ->limit(5)
            ->get()
            ->map(fn (Tournament $t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'location' => $t->location,
                'format' => $t->team_format->label(),
                'day' => $t->scheduled_at?->format('j') ?? '—',
                'month' => $t->scheduled_at !== null ? self::MONTHS_SHORT[$t->scheduled_at->month - 1] : '',
                'teams' => $t->teams_count,
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recent(User $user): array
    {
        return array_values($user->tournaments()
            ->where('status', TournamentStatus::Finished->value)
            ->withCount('teams')
            ->orderByDesc('finished_at')
            ->limit(5)
            ->get()
            ->map(fn (Tournament $t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'location' => $t->location,
                'date' => $this->frenchDate($t->finished_at),
                'teams' => $t->teams_count,
                'winner' => $t->teams()->where('final_rank', 1)->orderBy('division')->value('name'),
            ])
            ->all());
    }

    private function frenchDate(?Carbon $date): string
    {
        if ($date === null) {
            return '—';
        }

        return $date->day.' '.self::MONTHS_LONG[$date->month - 1].' '.$date->year;
    }
}
