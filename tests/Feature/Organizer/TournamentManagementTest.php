<?php

use App\Enums\TournamentStatus;
use App\Models\Court;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access tournaments', function () {
    $this->get('/organizer/tournaments')->assertRedirect('/login');
});

test('an organizer can see their tournaments index', function () {
    $user = User::factory()->create();
    Tournament::factory()->for($user)->create(['name' => 'Concours Test']);

    $this->actingAs($user)
        ->get('/organizer/tournaments')
        ->assertOk();
});

test('an organizer can create a tournament', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/organizer/tournaments', [
        'name' => 'Concours du village',
        'location' => 'Marseille',
        'scheduled_at' => '2026-08-01 09:00',
        'team_format' => 'doublette',
        'qualifying_rounds' => 3,
        'tableaux_count' => 2,
        'points_target' => 13,
        'max_teams' => 64,
    ]);

    $tournament = Tournament::first();

    expect($tournament)->not->toBeNull()
        ->and($tournament->user_id)->toBe($user->id)
        ->and($tournament->status)->toBe(TournamentStatus::Draft)
        ->and($tournament->registration_token)->not->toBeEmpty();

    $response->assertRedirect("/organizer/tournaments/{$tournament->id}");
});

test('creating a tournament requires a name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/organizer/tournaments', [
            'team_format' => 'doublette',
            'qualifying_rounds' => 3,
            'tableaux_count' => 1,
            'points_target' => 13,
        ])
        ->assertSessionHasErrors('name');

    expect(Tournament::count())->toBe(0);
});

test('an organizer can update a tournament', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create(['name' => 'Ancien nom']);

    $this->actingAs($user)->put("/organizer/tournaments/{$tournament->id}", [
        'name' => 'Nouveau nom',
        'location' => 'Aix',
        'team_format' => 'triplette',
        'qualifying_rounds' => 4,
        'tableaux_count' => 3,
        'points_target' => 13,
        'status' => 'registration_open',
    ])->assertRedirect("/organizer/tournaments/{$tournament->id}");

    $tournament->refresh();

    expect($tournament->name)->toBe('Nouveau nom')
        ->and($tournament->status)->toBe(TournamentStatus::RegistrationOpen);
});

test('an organizer can archive and restore a tournament', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();

    $this->actingAs($user)->patch("/organizer/tournaments/{$tournament->id}/archive");
    expect($tournament->fresh()->status)->toBe(TournamentStatus::Archived);

    $this->actingAs($user)->patch("/organizer/tournaments/{$tournament->id}/unarchive");
    expect($tournament->fresh()->status)->toBe(TournamentStatus::Draft);
});

test('an organizer cannot view a tournament owned by someone else', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = Tournament::factory()->for($owner)->create();

    $this->actingAs($other)
        ->get("/organizer/tournaments/{$tournament->id}")
        ->assertForbidden();
});

test('an organizer cannot update a tournament owned by someone else', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = Tournament::factory()->for($owner)->create();

    $this->actingAs($other)->put("/organizer/tournaments/{$tournament->id}", [
        'name' => 'Détournement',
        'team_format' => 'doublette',
        'qualifying_rounds' => 3,
        'tableaux_count' => 1,
        'points_target' => 13,
        'status' => 'draft',
    ])->assertForbidden();
});

test('an organizer can delete a tournament and its courts', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();
    Court::factory()->for($tournament)->count(2)->create();

    $this->actingAs($user)
        ->delete("/organizer/tournaments/{$tournament->id}")
        ->assertRedirect('/organizer/tournaments');

    expect(Tournament::count())->toBe(0)
        ->and(Court::count())->toBe(0);
});

test('an organizer can add a court', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();

    $this->actingAs($user)->post("/organizer/tournaments/{$tournament->id}/courts", [
        'label' => '1',
    ]);

    expect($tournament->courts()->count())->toBe(1);
});

test('court labels are unique per tournament', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();
    Court::factory()->for($tournament)->create(['label' => '1']);

    $this->actingAs($user)
        ->post("/organizer/tournaments/{$tournament->id}/courts", ['label' => '1'])
        ->assertSessionHasErrors('label');

    expect($tournament->courts()->count())->toBe(1);
});

test('an organizer can generate a series of numbered courts', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();

    $this->actingAs($user)
        ->post("/organizer/tournaments/{$tournament->id}/courts/generate", ['count' => 6]);

    expect($tournament->courts()->count())->toBe(6)
        ->and($tournament->courts()->pluck('label')->all())->toContain('1', '6');
});

test('generating courts continues after existing numbers', function () {
    $user = User::factory()->create();
    $tournament = Tournament::factory()->for($user)->create();
    Court::factory()->for($tournament)->create(['label' => '1']);
    Court::factory()->for($tournament)->create(['label' => '2']);

    $this->actingAs($user)
        ->post("/organizer/tournaments/{$tournament->id}/courts/generate", ['count' => 2]);

    expect($tournament->courts()->count())->toBe(4)
        ->and($tournament->courts()->pluck('label')->all())->toContain('3', '4');
});

test('an organizer cannot manage courts on a tournament they do not own', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tournament = Tournament::factory()->for($owner)->create();

    $this->actingAs($other)
        ->post("/organizer/tournaments/{$tournament->id}/courts", ['label' => '1'])
        ->assertForbidden();
});
