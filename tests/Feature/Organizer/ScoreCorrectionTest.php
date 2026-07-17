<?php

declare(strict_types=1);

use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Models\Court;
use App\Models\Matchup;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function correctionSetup(User $user, int $teams = 4, int $rounds = 2, int $tableaux = 2): Tournament
{
    $tournament = Tournament::factory()->for($user)->create([
        'status' => TournamentStatus::CheckIn,
        'team_format' => TeamFormat::Doublette,
        'qualifying_rounds' => $rounds,
        'tableaux_count' => $tableaux,
        'points_target' => 13,
    ]);
    for ($i = 1; $i <= $teams; $i++) {
        Team::create(['tournament_id' => $tournament->id, 'name' => "Équipe {$i}", 'seed' => $i]);
    }
    for ($c = 1; $c <= (int) ($teams / 2); $c++) {
        Court::create(['tournament_id' => $tournament->id, 'label' => (string) $c]);
    }

    return $tournament;
}

/** Joue via HTTP toutes les parties d'une phase/état (équipe A gagne). */
function playAllVia($test, User $user, Tournament $tournament, string $phase, string $status): void
{
    $guard = 0;
    while (true) {
        $matches = Matchup::where('tournament_id', $tournament->id)
            ->where('phase', $phase)->where('status', $status)->get();
        if ($matches->isEmpty()) {
            break;
        }
        foreach ($matches as $m) {
            $test->actingAs($user)->post("/organizer/matches/{$m->id}/result", ['score_a' => 13, 'score_b' => 7]);
        }
        if (++$guard > 500) {
            throw new RuntimeException('Boucle emballée.');
        }
    }
}

test('correcting a score without changing the winner just updates the score', function () {
    $user = User::factory()->create();
    $tournament = correctionSetup($user);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");

    // Joue toute la ronde 1 -> ronde 2 générée.
    playAllVia($this, $user, $tournament, 'qualification', 'playing');

    $round1 = $tournament->matches()->where('phase', 'qualification')->where('round', 1)->get();
    $round2IdsBefore = $tournament->matches()->where('round', 2)->pluck('id')->sort()->values()->all();

    $match = $round1->first(); // 13-7, équipe A gagnante
    $this->actingAs($user)->patch("/organizer/matches/{$match->id}/result", ['score_a' => 13, 'score_b' => 9]);

    $match->refresh();
    expect($match->score_b)->toBe(9)
        ->and($match->winner_team_id)->toBe($match->team_a_id)
        // La ronde 2 n'a pas bougé : aucun recalcul.
        ->and($tournament->matches()->where('round', 2)->pluck('id')->sort()->values()->all())
        ->toBe($round2IdsBefore);
});

test('correcting a score that flips the winner recalculates the following rounds', function () {
    $user = User::factory()->create();
    $tournament = correctionSetup($user);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");
    playAllVia($this, $user, $tournament, 'qualification', 'playing');

    $round2IdsBefore = $tournament->matches()->where('round', 2)->pluck('id')->all();

    // Inverse le vainqueur d'une partie de la ronde 1 : l'équipe B gagne.
    $match = $tournament->matches()->where('phase', 'qualification')->where('round', 1)->first();
    $this->actingAs($user)->patch("/organizer/matches/{$match->id}/result", ['score_a' => 7, 'score_b' => 13]);

    $match->refresh();
    expect($match->winner_team_id)->toBe($match->team_b_id);

    // La ronde 2 a été régénérée (nouvelles parties, non jouées).
    $round2After = $tournament->matches()->where('round', 2)->get();
    expect($round2After)->toHaveCount(2)
        ->and($round2After->pluck('id')->all())->not->toBe($round2IdsBefore)
        ->and($round2After->every(fn (Matchup $m) => $m->status !== 'finished'))->toBeTrue();

    // Le concours reste jouable jusqu'au bout après recalcul.
    playAllVia($this, $user, $tournament, 'qualification', 'playing');
    expect($tournament->matches()->where('phase', 'qualification')->where('status', 'playing')->count())->toBe(0);

    // Aucune revanche sur l'ensemble des parties de qualification.
    $pairs = $tournament->matches()->where('phase', 'qualification')->get()
        ->map(function (Matchup $m): string {
            $ids = [$m->team_a_id, $m->team_b_id];
            sort($ids);

            return implode('-', $ids);
        });
    expect($pairs->count())->toBe($pairs->unique()->count());
});

test('a knockout correction that flips the winner reopens the tournament', function () {
    $user = User::factory()->create();
    $tournament = correctionSetup($user, teams: 8, rounds: 3, tableaux: 3);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");
    playAllVia($this, $user, $tournament, 'qualification', 'playing');
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/finals/start");
    playAllVia($this, $user, $tournament, 'knockout', 'ready');

    expect($tournament->fresh()->status)->toBe(TournamentStatus::Finished);

    // Corrige une finale non-finale, en inversant le vainqueur.
    $match = $tournament->matches()->where('phase', 'knockout')->where('status', 'finished')
        ->where('is_walkover', false)->orderBy('round')->first();
    $this->actingAs($user)->patch("/organizer/matches/{$match->id}/result", ['score_a' => 7, 'score_b' => 13]);

    $match->refresh();
    expect($match->winner_team_id)->toBe($match->team_b_id);

    // Le tableau a des parties à rejouer -> le concours n'est plus « terminé ».
    $tournament->refresh();
    expect($tournament->current_phase)->not->toBe('completed');
});

test('only a finished match can be corrected', function () {
    $user = User::factory()->create();
    $tournament = correctionSetup($user);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");

    $playing = $tournament->matches()->where('status', 'playing')->first();
    $this->actingAs($user)->patch("/organizer/matches/{$playing->id}/result", ['score_a' => 13, 'score_b' => 7]);

    expect($playing->fresh()->status)->toBe('playing'); // inchangé (refusé)
});

test('a non-owner cannot correct a result', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = correctionSetup($owner);
    $this->actingAs($owner)->post("/organizer/tournaments/{$tournament->id}/qualification/start");
    playAllVia($this, $owner, $tournament, 'qualification', 'playing');

    $match = $tournament->matches()->where('round', 1)->first();
    $this->actingAs($other)
        ->patch("/organizer/matches/{$match->id}/result", ['score_a' => 7, 'score_b' => 13])
        ->assertForbidden();
});

test('a team cannot be removed once the tournament has started', function () {
    $user = User::factory()->create();
    $tournament = correctionSetup($user);
    $team = $tournament->teams()->first();
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");

    $this->actingAs($user)->delete("/organizer/teams/{$team->id}");

    expect(Team::whereKey($team->id)->exists())->toBeTrue(); // toujours là
});
