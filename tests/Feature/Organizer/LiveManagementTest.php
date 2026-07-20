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

function liveTournament(User $user, int $teams = 4, int $rounds = 2, int $tableaux = 2): Tournament
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

/** Joue toutes les parties actionnables d'une phase via la route de saisie (équipe A gagne). */
function playPhaseViaHttp($test, User $user, Tournament $tournament, string $phase, string $status): void
{
    $guard = 0;
    while (true) {
        $matches = Matchup::where('tournament_id', $tournament->id)
            ->where('phase', $phase)->where('status', $status)->get();

        if ($matches->isEmpty()) {
            break;
        }

        foreach ($matches as $match) {
            $test->actingAs($user)->post("/organizer/matches/{$match->id}/result", [
                'score_a' => 13,
                'score_b' => 7,
            ]);
        }

        if (++$guard > 500) {
            throw new RuntimeException('Boucle emballée.');
        }
    }
}

test('the live page renders and offers to start when ready', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user, teams: 2);

    $this->actingAs($user)
        ->get("/organizer/tournaments/{$tournament->id}/live")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('organizer/tournaments/live')
            ->where('canStartQualification', true)
            ->where('qualification', null)
            ->where('finals', null));
});

test('starting qualification creates the first round', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user);

    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");

    $tournament->refresh();
    expect($tournament->current_phase)->toBe('qualification')
        ->and($tournament->matches()->where('phase', 'qualification')->where('status', 'playing')->count())->toBe(2);
});

test('the live page suggests a format based on team count', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user, teams: 16);

    $this->actingAs($user)
        ->get("/organizer/tournaments/{$tournament->id}/live")
        ->assertInertia(fn ($page) => $page
            ->where('formatSuggestion.qualifying_rounds', 4)
            ->where('formatSuggestion.tableaux_count', 3)
            ->where('formatSuggestion.points_target', 13));
});

test('starting qualification persists the format chosen at the draw', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user, teams: 4, rounds: 2, tableaux: 1);

    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start", [
        'qualifying_rounds' => 3,
        'tableaux_count' => 2,
        'points_target' => 11,
    ]);

    $tournament->refresh();
    expect($tournament->current_phase)->toBe('qualification')
        ->and($tournament->qualifying_rounds)->toBe(3)
        ->and($tournament->tableaux_count)->toBe(2)
        ->and($tournament->points_target)->toBe(11);
});

test('starting qualification without a format keeps the stored one', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user, teams: 4, rounds: 2, tableaux: 2);

    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");

    $tournament->refresh();
    expect($tournament->qualifying_rounds)->toBe(2)
        ->and($tournament->tableaux_count)->toBe(2);
});

test('the live page exposes standings with points for and against', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user, teams: 4, rounds: 2, tableaux: 2);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");

    $match = $tournament->matches()->where('status', 'playing')->first();
    $this->actingAs($user)->post("/organizer/matches/{$match->id}/result", ['score_a' => 13, 'score_b' => 6]);

    $this->actingAs($user)
        ->get("/organizer/tournaments/{$tournament->id}/live")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('qualification.standings.0.points_for')
            ->has('qualification.standings.0.points_against')
            ->where('qualification.standings.0.wins', 1)
            ->where('qualification.standings.0.points_for', 13));
});

test('recording a result via the route finishes the match', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");

    $match = $tournament->matches()->where('status', 'playing')->first();
    $this->actingAs($user)->post("/organizer/matches/{$match->id}/result", ['score_a' => 13, 'score_b' => 9]);

    expect($match->fresh()->status)->toBe('finished')
        ->and($match->fresh()->winner_team_id)->toBe($match->team_a_id);
});

test('an invalid score is rejected by the engine and leaves the match untouched', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");

    $match = $tournament->matches()->where('status', 'playing')->first();
    // Score sans vainqueur à 13 : refusé par le moteur.
    $this->actingAs($user)->post("/organizer/matches/{$match->id}/result", ['score_a' => 10, 'score_b' => 8]);

    expect($match->fresh()->status)->toBe('playing');
});

test('a non-owner cannot pilot the tournament', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = liveTournament($owner);

    $this->actingAs($other)
        ->post("/organizer/tournaments/{$tournament->id}/qualification/start")
        ->assertForbidden();
});

test('a full tournament can be run entirely through the live routes', function () {
    $user = User::factory()->create();
    $tournament = liveTournament($user, teams: 8, rounds: 3, tableaux: 3);

    // Lancer + jouer les qualifications.
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/qualification/start");
    playPhaseViaHttp($this, $user, $tournament, 'qualification', 'playing');

    // Lancer + jouer les finales.
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/finals/start");
    expect($tournament->fresh()->current_phase)->toBe('finals')
        ->and($tournament->matches()->where('phase', 'knockout')->exists())->toBeTrue();

    playPhaseViaHttp($this, $user, $tournament, 'knockout', 'ready');

    // Le concours est terminé et toutes les équipes sont classées.
    $tournament->refresh();
    expect($tournament->status)->toBe(TournamentStatus::Finished)
        ->and($tournament->current_phase)->toBe('completed')
        ->and($tournament->teams()->whereNull('final_rank')->count())->toBe(0);
});
