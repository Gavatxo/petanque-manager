<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the tournament page exposes the inscription QR code and registered teams', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();
    Team::factory()->for($tournament)->create(['seed' => 1]);

    $this->actingAs($user)
        ->get("/organizer/tournaments/{$tournament->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('organizer/tournaments/show')
            ->where('registrationQr', fn (string $qr) => str_starts_with($qr, 'data:image/svg+xml'))
            ->has('teams', 1));
});

test('the QR route returns an SVG image', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();

    $this->actingAs($user)
        ->get("/organizer/tournaments/{$tournament->id}/qr")
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

test('an organizer can remove a registered team', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();
    $team = Team::factory()->for($tournament)->create(['seed' => 1]);

    $this->actingAs($user)
        ->delete("/organizer/teams/{$team->id}")
        ->assertRedirect();

    expect(Team::count())->toBe(0);
});

test('an organizer cannot remove a team from another organizer tournament', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = Tournament::factory()->for($owner)->create();
    $team = Team::factory()->for($tournament)->create(['seed' => 1]);

    $this->actingAs($other)
        ->delete("/organizer/teams/{$team->id}")
        ->assertForbidden();

    expect(Team::count())->toBe(1);
});
