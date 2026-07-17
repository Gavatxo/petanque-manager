<?php

declare(strict_types=1);

use App\Application\Tournament\RecordMatchResult;
use App\Application\Tournament\StartQualification;
use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Events\TournamentUpdated;
use App\Models\Court;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function realtimeTournament(): Tournament
{
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::CheckIn,
        'team_format' => TeamFormat::Doublette,
        'qualifying_rounds' => 1,
        'tableaux_count' => 2,
        'points_target' => 13,
    ]);
    Team::create(['tournament_id' => $tournament->id, 'name' => 'Équipe 1', 'seed' => 1]);
    Team::create(['tournament_id' => $tournament->id, 'name' => 'Équipe 2', 'seed' => 2]);
    Court::create(['tournament_id' => $tournament->id, 'label' => '1']);

    return $tournament;
}

test('the broadcast event targets a public tournament channel', function () {
    $event = new TournamentUpdated(42);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class)
        ->and($event->broadcastOn())->toBeInstanceOf(Channel::class)
        ->and($event->broadcastOn()->name)->toBe('tournament.42')
        ->and($event->broadcastAs())->toBe('updated');
});

test('starting qualification broadcasts an update', function () {
    $tournament = realtimeTournament();
    Event::fake([TournamentUpdated::class]);

    app(StartQualification::class)->handle($tournament);

    Event::assertDispatched(
        TournamentUpdated::class,
        fn (TournamentUpdated $e): bool => $e->tournamentId === $tournament->id,
    );
});

test('recording a result broadcasts an update', function () {
    $tournament = realtimeTournament();
    app(StartQualification::class)->handle($tournament);

    Event::fake([TournamentUpdated::class]);

    $match = $tournament->matches()->where('status', 'playing')->first();
    app(RecordMatchResult::class)->handle($match, 13, 7);

    Event::assertDispatched(
        TournamentUpdated::class,
        fn (TournamentUpdated $e): bool => $e->tournamentId === $tournament->id,
    );
});

test('a public registration broadcasts an update to the organizer screens', function () {
    $tournament = Tournament::factory()->create([
        'status' => TournamentStatus::RegistrationOpen,
        'team_format' => TeamFormat::Doublette,
    ]);
    Event::fake([TournamentUpdated::class]);

    $this->post("/i/{$tournament->registration_token}", [
        'team_name' => 'Les Rapides',
        'players' => [
            ['first_name' => 'A', 'last_name' => 'Un'],
            ['first_name' => 'B', 'last_name' => 'Deux'],
        ],
    ]);

    Event::assertDispatched(
        TournamentUpdated::class,
        fn (TournamentUpdated $e): bool => $e->tournamentId === $tournament->id,
    );
});
