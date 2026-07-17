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

test('an organizer can register a team manually as confirmed', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['team_format' => TeamFormat::Doublette]);

    $this->actingAs($user)
        ->post("/organizer/tournaments/{$tournament->id}/registrations", manualPayload())
        ->assertRedirect();

    $registration = Registration::first();

    expect($registration)->not->toBeNull()
        ->and($registration->team_name)->toBe('Les Ajoutés')
        ->and($registration->status)->toBe(RegistrationStatus::Confirmed)
        ->and($registration->confirmed_at)->not->toBeNull()
        ->and($registration->players()->count())->toBe(2)
        ->and($registration->players()->where('is_captain', true)->count())->toBe(1);
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
