<?php

declare(strict_types=1);

use App\Application\Tournament\CompleteQualification;
use App\Application\Tournament\RecordMatchResult;
use App\Application\Tournament\StartFinals;
use App\Application\Tournament\StartQualification;
use App\Enums\TournamentStatus;
use App\Models\Court;
use App\Models\Matchup;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Rejoue toutes les parties d'une phase/état donné jusqu'à épuisement.
 * Le vainqueur est choisi de façon déterministe par $winnerPicker(seedA, seedB).
 *
 * @param  callable(int, int): bool  $aWins  vrai si l'équipe A l'emporte
 */
function playOut(Tournament $tournament, string $phase, string $status, callable $aWins): void
{
    $recorder = app(RecordMatchResult::class);
    $seeds = $tournament->teams()->pluck('seed', 'id');
    $divisionSeeds = $tournament->teams()->pluck('division_seed', 'id');

    $guard = 0;

    while (true) {
        $matches = $tournament->matches()->where('phase', $phase)->where('status', $status)->get();

        if ($matches->isEmpty()) {
            break;
        }

        foreach ($matches as $match) {
            /** @var Matchup $match */
            $keyA = $phase === 'knockout' ? $divisionSeeds : $seeds;
            $valueA = (int) $keyA[$match->team_a_id];
            $valueB = (int) $keyA[$match->team_b_id];

            if ($aWins($valueA, $valueB)) {
                $recorder->handle($match, 13, 7);
            } else {
                $recorder->handle($match, 7, 13);
            }
        }

        if (++$guard > 1000) {
            throw new RuntimeException('Boucle de jeu emballée.');
        }
    }
}

test('un concours de 20 équipes va des qualifications au classement final', function () {
    $user = User::factory()->create();

    $tournament = Tournament::factory()->for($user)->create([
        'qualifying_rounds' => 4,
        'tableaux_count' => 3,   // A / B / C
        'points_target' => 13,
        'status' => TournamentStatus::CheckIn,
    ]);

    for ($i = 1; $i <= 20; $i++) {
        Team::create(['tournament_id' => $tournament->id, 'name' => "Équipe {$i}", 'seed' => $i]);
    }

    for ($c = 1; $c <= 10; $c++) {
        Court::create(['tournament_id' => $tournament->id, 'label' => (string) $c]);
    }

    // --- Qualifications : 4 rondes, meilleur seed vainqueur ---
    app(StartQualification::class)->handle($tournament);

    expect($tournament->fresh()->current_phase)->toBe('qualification')
        ->and($tournament->matches()->where('phase', 'qualification')->count())->toBe(10);

    playOut($tournament, 'qualification', 'playing', fn (int $a, int $b): bool => $a > $b);

    // 4 rondes × 10 parties = 40 parties de qualification, toutes terminées.
    expect($tournament->matches()->where('phase', 'qualification')->count())->toBe(40)
        ->and($tournament->matches()->where('phase', 'qualification')->where('status', 'finished')->count())->toBe(40);

    // Aucune revanche en qualification.
    $pairs = $tournament->matches()->where('phase', 'qualification')->get()
        ->map(function (Matchup $m): string {
            $ids = [$m->team_a_id, $m->team_b_id];
            sort($ids);

            return implode('-', $ids);
        });
    expect($pairs->count())->toBe($pairs->unique()->count());

    // --- Clôture : divisions A/B/C ---
    app(CompleteQualification::class)->handle($tournament->fresh());

    $tournament->load('teams');
    foreach ($tournament->teams as $team) {
        expect($team->division)->toBeIn(['A', 'B', 'C'])
            ->and($team->division_seed)->not->toBeNull();
    }
    expect($tournament->teams()->count())->toBe(20);

    // --- Phases finales : meilleur seed de division vainqueur ---
    app(StartFinals::class)->handle($tournament->fresh());

    expect($tournament->fresh()->current_phase)->toBe('finals')
        ->and($tournament->matches()->where('phase', 'knockout')->exists())->toBeTrue();

    playOut($tournament, 'knockout', 'ready', fn (int $a, int $b): bool => $a < $b);

    // --- Vérification du classement final ---
    $tournament->refresh();

    expect($tournament->status)->toBe(TournamentStatus::Finished)
        ->and($tournament->current_phase)->toBe('completed');

    $tournament->load('teams');

    // Toutes les équipes ont une place finale.
    foreach ($tournament->teams as $team) {
        expect($team->final_rank)->not->toBeNull();
    }

    // Dans chaque division, les places sont 1..N sans doublon, avec une seule championne.
    foreach ($tournament->teams->groupBy('division') as $division => $teams) {
        $ranks = $teams->pluck('final_rank')->sort()->values()->all();
        expect($ranks)->toBe(range(1, $teams->count()))
            ->and($teams->where('final_rank', 1)->count())->toBe(1);
    }

    // Chaque partie de finale est terminée (ou exempt).
    foreach ($tournament->matches()->where('phase', 'knockout')->get() as $match) {
        expect($match->status)->toBeIn(['finished', 'bye']);
    }
});
