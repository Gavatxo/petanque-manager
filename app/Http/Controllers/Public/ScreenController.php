<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Matchup;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Écran public plein écran (TV du boulodrome) : l'état des terrains en direct.
 * Accès par le token du concours, aucune interaction ni donnée d'administration.
 */
class ScreenController extends Controller
{
    public function show(Tournament $tournament): Response
    {
        $tournament->load(['courts', 'teams']);

        /** @var array<int, string> $names */
        $names = $tournament->teams->mapWithKeys(fn ($t) => [$t->id => $t->name])->all();

        /** @var Collection<int, Collection<int, Matchup>> $byCourt */
        $byCourt = $tournament->matches()
            ->whereNotNull('court_id')
            ->get()
            ->groupBy('court_id');

        $currentRound = $tournament->matches()->where('phase', 'qualification')->max('round') ?? 0;

        return Inertia::render('public/screen', [
            'tournamentId' => $tournament->id,
            'club' => $tournament->location ?? 'Boulodrome',
            'name' => $tournament->name,
            'subtitle' => $this->subtitle($tournament, (int) $currentRound),
            'courts' => $tournament->courts
                ->sortBy('label', SORT_NATURAL)
                ->map(fn (Court $court) => $this->courtState($court, $byCourt->get($court->id), $names))
                ->values(),
        ]);
    }

    private function subtitle(Tournament $tournament, int $currentRound): string
    {
        $phase = match ($tournament->current_phase) {
            'qualification' => "Qualifications · Ronde {$currentRound} / {$tournament->qualifying_rounds}",
            'finals' => 'Phases finales',
            'completed' => 'Concours terminé',
            default => 'À venir',
        };

        return $phase.' · '.$tournament->team_format->label().' · '.$tournament->teams->count().' équipes';
    }

    /**
     * @param  Collection<int, Matchup>|null  $matches
     * @param  array<int, string>  $names
     * @return array<string, mixed>
     */
    private function courtState(Court $court, ?Collection $matches, array $names): array
    {
        $matches ??= collect();
        $playing = $matches->firstWhere('status', 'playing');

        if ($playing !== null) {
            return [
                'label' => $court->label,
                'status' => 'playing',
                'team_a' => $playing->team_a_id !== null ? ($names[$playing->team_a_id] ?? null) : null,
                'team_b' => $playing->team_b_id !== null ? ($names[$playing->team_b_id] ?? null) : null,
                'score_a' => null,
                'score_b' => null,
                'winner_a' => false,
                'winner_b' => false,
            ];
        }

        /** @var Matchup|null $finished */
        $finished = $matches->where('status', 'finished')->sortByDesc('result_sequence')->first();

        if ($finished !== null) {
            return [
                'label' => $court->label,
                'status' => 'finished',
                'team_a' => $finished->team_a_id !== null ? ($names[$finished->team_a_id] ?? null) : null,
                'team_b' => $finished->team_b_id !== null ? ($names[$finished->team_b_id] ?? null) : null,
                'score_a' => $finished->score_a,
                'score_b' => $finished->score_b,
                'winner_a' => $finished->winner_team_id === $finished->team_a_id,
                'winner_b' => $finished->winner_team_id === $finished->team_b_id,
            ];
        }

        return [
            'label' => $court->label,
            'status' => 'free',
            'team_a' => null,
            'team_b' => null,
            'score_a' => null,
            'score_b' => null,
            'winner_a' => false,
            'winner_b' => false,
        ];
    }
}
