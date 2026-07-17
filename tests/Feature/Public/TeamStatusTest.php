<?php

declare(strict_types=1);

use App\Application\Tournament\StartQualification;
use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Models\Court;
use App\Models\Registration;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the follow page works before the team is engaged', function () {
    $tournament = Tournament::factory()->create();
    $registration = Registration::factory()->for($tournament)->create(['team_name' => 'Les Boulistes']);

    $this->get("/suivi/{$registration->follow_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/team-status')
            ->where('teamName', 'Les Boulistes')
            ->where('team', null));
});

test('the follow page shows a live "playing" status with opponent and court', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::CheckIn,
        'team_format' => TeamFormat::Doublette,
        'qualifying_rounds' => 2,
        'tableaux_count' => 2,
        'points_target' => 13,
    ]);
    Court::create(['tournament_id' => $tournament->id, 'label' => '1']);

    $reg1 = Registration::factory()->for($tournament)->checkedIn()->create();
    $reg2 = Registration::factory()->for($tournament)->checkedIn()->create();
    Team::create(['tournament_id' => $tournament->id, 'registration_id' => $reg1->id, 'name' => 'Nous', 'seed' => 1]);
    Team::create(['tournament_id' => $tournament->id, 'registration_id' => $reg2->id, 'name' => 'Eux', 'seed' => 2]);

    app(StartQualification::class)->handle($tournament);

    $this->get("/suivi/{$reg1->follow_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/team-status')
            ->where('team.live.key', 'playing')
            ->where('team.live.opponent', 'Eux')
            ->where('team.live.court', '1'));
});
