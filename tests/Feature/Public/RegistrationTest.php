<?php

declare(strict_types=1);

use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function openTournament(array $attributes = []): Tournament
{
    return Tournament::factory()->create(array_merge([
        'status' => TournamentStatus::RegistrationOpen,
        'team_format' => TeamFormat::Doublette,
        'max_teams' => null,
    ], $attributes));
}

function doublettePayload(string $teamName = 'Les Fanny’s'): array
{
    return [
        'team_name' => $teamName,
        'players' => [
            ['first_name' => 'Marius', 'last_name' => 'Olive', 'phone' => '0600000000'],
            ['first_name' => 'César', 'last_name' => 'Panisse'],
        ],
    ];
}

test('anyone can view the public registration page', function () {
    $tournament = openTournament();

    $this->get("/i/{$tournament->registration_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/register')
            ->where('teamSize', 2)
            ->where('registrationOpen', true));
});

test('a player can register a team with its players', function () {
    $tournament = openTournament();

    $response = $this->post("/i/{$tournament->registration_token}", doublettePayload());

    $team = Team::first();

    expect($team)->not->toBeNull()
        ->and($team->tournament_id)->toBe($tournament->id)
        ->and($team->name)->toBe('Les Fanny’s')
        ->and($team->seed)->toBe(1)
        ->and($team->follow_token)->not->toBeNull()
        ->and($team->players()->count())->toBe(2)
        ->and($team->players()->where('is_captain', true)->count())->toBe(1);

    $response->assertRedirect("/inscription/confirmee/{$team->follow_token}");
});

test('registration numbers teams in order', function () {
    $tournament = openTournament();

    $this->post("/i/{$tournament->registration_token}", doublettePayload('Équipe A'));
    $this->post("/i/{$tournament->registration_token}", doublettePayload('Équipe B'));

    expect(Team::orderBy('seed')->pluck('seed')->all())->toBe([1, 2]);
});

test('registration requires the exact number of players for the format', function () {
    $tournament = openTournament(); // doublette -> 2 joueurs

    $this->post("/i/{$tournament->registration_token}", [
        'players' => [['first_name' => 'Seul', 'last_name' => 'Joueur']],
    ])->assertSessionHasErrors('players');

    expect(Team::count())->toBe(0);
});

test('registration is forbidden when it is not open', function () {
    $tournament = openTournament(['status' => TournamentStatus::Draft]);

    $this->get("/i/{$tournament->registration_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('registrationOpen', false));

    $this->post("/i/{$tournament->registration_token}", doublettePayload())
        ->assertForbidden();

    expect(Team::count())->toBe(0);
});

test('registration is refused once the tournament is full', function () {
    $tournament = openTournament(['max_teams' => 1]);
    Team::factory()->for($tournament)->create(['seed' => 1]);

    $this->post("/i/{$tournament->registration_token}", doublettePayload())
        ->assertSessionHasErrors('players');

    expect($tournament->teams()->count())->toBe(1);
});

test('the confirmation page shows the registered team', function () {
    $tournament = openTournament();
    $this->post("/i/{$tournament->registration_token}", doublettePayload('Les Cadors'));
    $team = Team::first();

    $this->get("/inscription/confirmee/{$team->follow_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/registered')
            ->where('team.name', 'Les Cadors')
            ->has('team.players', 2));
});
