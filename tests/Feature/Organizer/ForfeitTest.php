<?php

declare(strict_types=1);

use App\Application\Tournament\StartQualification;
use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Models\Court;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function startedTournament(User $user): Tournament
{
    $tournament = Tournament::factory()->for($user)->create([
        'status' => TournamentStatus::CheckIn,
        'team_format' => TeamFormat::Doublette,
        'qualifying_rounds' => 2,
        'tableaux_count' => 2,
        'points_target' => 13,
    ]);
    for ($i = 1; $i <= 4; $i++) {
        Team::create(['tournament_id' => $tournament->id, 'name' => "Équipe {$i}", 'seed' => $i]);
    }
    Court::create(['tournament_id' => $tournament->id, 'label' => '1']);
    Court::create(['tournament_id' => $tournament->id, 'label' => '2']);

    app(StartQualification::class)->handle($tournament);

    return $tournament;
}

test('a forfeit gives the win to the opponent with a full score', function () {
    $user = User::factory()->create();
    $tournament = startedTournament($user);
    $match = $tournament->matches()->where('status', 'playing')->first();

    // L'équipe A déclare forfait -> B gagne 13-0.
    $this->actingAs($user)
        ->post("/organizer/matches/{$match->id}/forfeit", ['forfeiting_team_id' => $match->team_a_id])
        ->assertRedirect();

    $match->refresh();
    expect($match->status)->toBe('finished')
        ->and($match->is_forfeit)->toBeTrue()
        ->and($match->winner_team_id)->toBe($match->team_b_id)
        ->and($match->score_a)->toBe(0)
        ->and($match->score_b)->toBe(13);
});

test('the opponent that forfeits determines who wins', function () {
    $user = User::factory()->create();
    $tournament = startedTournament($user);
    $match = $tournament->matches()->where('status', 'playing')->first();

    // L'équipe B déclare forfait -> A gagne.
    $this->actingAs($user)
        ->post("/organizer/matches/{$match->id}/forfeit", ['forfeiting_team_id' => $match->team_b_id]);

    $match->refresh();
    expect($match->winner_team_id)->toBe($match->team_a_id)
        ->and($match->score_a)->toBe(13)
        ->and($match->score_b)->toBe(0);
});

test('forfeiting a team that does not play the match is rejected', function () {
    $user = User::factory()->create();
    $tournament = startedTournament($user);
    $match = $tournament->matches()->where('status', 'playing')->first();
    $stranger = $tournament->teams()->whereNotIn('id', [$match->team_a_id, $match->team_b_id])->first();

    $this->actingAs($user)
        ->post("/organizer/matches/{$match->id}/forfeit", ['forfeiting_team_id' => $stranger->id]);

    expect($match->fresh()->status)->toBe('playing'); // inchangé
});

test('a non-owner cannot declare a forfeit', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = startedTournament($owner);
    $match = $tournament->matches()->where('status', 'playing')->first();

    $this->actingAs($other)
        ->post("/organizer/matches/{$match->id}/forfeit", ['forfeiting_team_id' => $match->team_a_id])
        ->assertForbidden();
});
