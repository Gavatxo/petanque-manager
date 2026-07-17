<?php

declare(strict_types=1);

use App\Application\Tournament\StartQualification;
use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Models\Court;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the public screen shows courts and is reachable by token', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::CheckIn,
        'team_format' => TeamFormat::Doublette,
        'qualifying_rounds' => 2,
        'tableaux_count' => 2,
        'points_target' => 13,
        'location' => 'Boulodrome de Marseille',
    ]);
    for ($i = 1; $i <= 4; $i++) {
        Team::create(['tournament_id' => $tournament->id, 'name' => "Équipe {$i}", 'seed' => $i]);
    }
    Court::create(['tournament_id' => $tournament->id, 'label' => '1']);
    Court::create(['tournament_id' => $tournament->id, 'label' => '2']);
    Court::create(['tournament_id' => $tournament->id, 'label' => '3']);

    app(StartQualification::class)->handle($tournament);

    $this->get("/ecran/{$tournament->registration_token}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/screen')
            ->where('club', 'Boulodrome de Marseille')
            ->has('courts', 3)
            // 4 équipes -> 2 parties en cours sur 2 terrains, le 3e est libre.
            ->where('courts', fn ($courts) => collect($courts)->where('status', 'playing')->count() === 2
                && collect($courts)->where('status', 'free')->count() === 1));
});
