<?php

declare(strict_types=1);

use App\Enums\RegistrationStatus;
use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Models\Registration;
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

test('a public submission creates a pending registration, not an official team', function () {
    $tournament = openTournament();

    $response = $this->post("/i/{$tournament->registration_token}", doublettePayload());

    $registration = Registration::first();

    expect($registration)->not->toBeNull()
        ->and($registration->tournament_id)->toBe($tournament->id)
        ->and($registration->team_name)->toBe('Les Fanny’s')
        ->and($registration->status)->toBe(RegistrationStatus::Pending)
        ->and($registration->follow_token)->not->toBeNull()
        ->and($registration->players()->count())->toBe(2)
        ->and($registration->players()->where('is_captain', true)->count())->toBe(1)
        // Aucune équipe officielle tant que l'organisateur n'a pas validé.
        ->and(Team::count())->toBe(0);

    $response->assertRedirect("/inscription/confirmee/{$registration->follow_token}");
});

test('registration requires the exact number of players for the format', function () {
    $tournament = openTournament(); // doublette -> 2 joueurs

    $this->post("/i/{$tournament->registration_token}", [
        'players' => [['first_name' => 'Seul', 'last_name' => 'Joueur']],
    ])->assertSessionHasErrors('players');

    expect(Registration::count())->toBe(0);
});

test('registration is forbidden when it is not open', function () {
    $tournament = openTournament(['status' => TournamentStatus::Draft]);

    $this->get("/i/{$tournament->registration_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('registrationOpen', false));

    $this->post("/i/{$tournament->registration_token}", doublettePayload())
        ->assertForbidden();

    expect(Registration::count())->toBe(0);
});

test('registration is refused once the active registrations reach the limit', function () {
    $tournament = openTournament(['max_teams' => 1]);
    Registration::factory()->for($tournament)->create();

    $this->post("/i/{$tournament->registration_token}", doublettePayload())
        ->assertSessionHasErrors('players');

    expect($tournament->registrations()->count())->toBe(1);
});

test('the confirmation page shows the pending registration', function () {
    $tournament = openTournament();
    $this->post("/i/{$tournament->registration_token}", doublettePayload('Les Cadors'));
    $registration = Registration::first();

    $this->get("/inscription/confirmee/{$registration->follow_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/registered')
            ->where('registration.team_name', 'Les Cadors')
            ->where('registration.status', 'pending')
            ->has('registration.players', 2));
});
