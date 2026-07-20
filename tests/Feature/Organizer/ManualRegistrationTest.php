<?php

declare(strict_types=1);

use App\Enums\RegistrationStatus;
use App\Enums\TeamFormat;
use App\Models\Registration;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function manualPayload(string $name = 'Les Ajoutés'): array
{
    return [
        'team_name' => $name,
        'players' => [
            ['first_name' => 'Jean', 'last_name' => 'Boule'],
            ['first_name' => 'Paul', 'last_name' => 'Cochonnet'],
        ],
    ];
}

test('an organizer can register a team manually with presence validated and a number', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['team_format' => TeamFormat::Doublette]);

    $this->actingAs($user)
        ->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload())
        ->assertRedirect();

    $registration = Registration::first();

    expect($registration)->not->toBeNull()
        ->and($registration->team_name)->toBe('Les Ajoutés')
        ->and($registration->status)->toBe(RegistrationStatus::CheckedIn)
        ->and($registration->confirmed_at)->not->toBeNull()
        ->and($registration->checked_in_at)->not->toBeNull()
        ->and($registration->number)->toBe(1)
        ->and($registration->players()->count())->toBe(2)
        ->and($registration->players()->where('is_captain', true)->count())->toBe(1);
});

test('team numbers are assigned in order across manual add and check-in', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['team_format' => TeamFormat::Doublette]);

    // Deux ajouts manuels : numéros 1 puis 2.
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload('A'));
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload('B'));

    // Une inscription via lien, puis validation de présence : numéro 3.
    $linked = Registration::factory()->for($tournament)->create();
    $this->actingAs($user)->patch("/organizer/registrations/{$linked->id}/check-in");

    expect(Registration::where('team_name', 'A')->first()->number)->toBe(1)
        ->and(Registration::where('team_name', 'B')->first()->number)->toBe(2)
        ->and($linked->fresh()->number)->toBe(3);
});

test('the team number becomes the official team seed at conversion', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['team_format' => TeamFormat::Doublette]);

    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload('A'));
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload('B'));
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations/create-teams");

    $a = Registration::where('team_name', 'A')->first();
    expect($a->team->seed)->toBe($a->number);
});

test('an organizer can edit a saved team name and players', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['team_format' => TeamFormat::Doublette]);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload('Faute de frappe'));
    $registration = Registration::first();

    $this->actingAs($user)->put("/organizer/registrations/{$registration->id}", [
        'team_name' => 'Nom Corrigé',
        'players' => [
            ['first_name' => 'Alice', 'last_name' => 'Un'],
            ['first_name' => 'Bob', 'last_name' => 'Deux'],
        ],
    ])->assertRedirect();

    $registration->refresh();
    expect($registration->team_name)->toBe('Nom Corrigé')
        ->and($registration->number)->toBe(1) // le numéro ne change pas
        ->and($registration->players()->count())->toBe(2)
        ->and($registration->players()->where('first_name', 'Alice')->exists())->toBeTrue();
});

test('editing a registration syncs the already-created official team', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['team_format' => TeamFormat::Doublette]);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload('Avant'));
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations/create-teams");
    $registration = Registration::first();

    $this->actingAs($user)->put("/organizer/registrations/{$registration->id}", [
        'team_name' => 'Après',
        'players' => [
            ['first_name' => 'Alice', 'last_name' => 'Un'],
            ['first_name' => 'Bob', 'last_name' => 'Deux'],
        ],
    ]);

    $team = $registration->fresh()->team;
    expect($team->name)->toBe('Après')
        ->and($team->players()->where('first_name', 'Alice')->exists())->toBeTrue();
});

test('the registrations page exposes team numbers and the started flag', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['team_format' => TeamFormat::Doublette]);
    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload('A'));

    $this->actingAs($user)
        ->get("/organizer/tournaments/{$tournament->id}/registrations")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('organizer/tournaments/registrations')
            ->where('started', false)
            ->where('registrations.0.number', 1)
            ->where('registrations.0.status', 'checked_in'));
});

test('a manual registration requires the right number of players', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['team_format' => TeamFormat::Triplette]);

    $this->actingAs($user)
        ->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload())
        ->assertSessionHasErrors('players'); // 2 joueurs fournis, 3 attendus

    expect(Registration::count())->toBe(0);
});

test('an organizer cannot add a team to another organizer tournament', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = Tournament::factory()->for($owner)->create(['team_format' => TeamFormat::Doublette]);

    $this->actingAs($other)
        ->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload())
        ->assertForbidden();

    expect(Registration::count())->toBe(0);
});

test('manual registration respects the maximum number of teams', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create([
        'team_format' => TeamFormat::Doublette,
        'max_teams' => 1,
    ]);
    Registration::factory()->for($tournament)->create();

    $this->actingAs($user)
        ->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload())
        ->assertSessionHasErrors('players');

    expect($tournament->registrations()->count())->toBe(1);
});
