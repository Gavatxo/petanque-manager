<?php

declare(strict_types=1);

use App\Application\Tournament\CompleteQualification;
use App\Application\Tournament\Exception\TournamentWorkflowException;
use App\Application\Tournament\StartQualification;
use App\Enums\TournamentStatus;
use App\Models\Court;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tournamentWithTeams(int $teamCount, int $courtCount = 4): Tournament
{
    $tournament = Tournament::factory()->for(User::factory())->create([
        'qualifying_rounds' => 3,
        'tableaux_count' => 3,
        'points_target' => 13,
        'status' => TournamentStatus::CheckIn,
    ]);

    for ($i = 1; $i <= $teamCount; $i++) {
        Team::create(['tournament_id' => $tournament->id, 'name' => "Équipe {$i}", 'seed' => $i]);
    }

    for ($c = 1; $c <= $courtCount; $c++) {
        Court::create(['tournament_id' => $tournament->id, 'label' => (string) $c]);
    }

    return $tournament;
}

test('démarrer les qualifications crée la première ronde et occupe les terrains', function () {
    $tournament = tournamentWithTeams(8, courtCount: 4);

    app(StartQualification::class)->handle($tournament);

    $tournament->refresh();

    expect($tournament->current_phase)->toBe('qualification')
        ->and($tournament->status)->toBe(TournamentStatus::Running)
        ->and($tournament->matches()->where('phase', 'qualification')->where('status', 'playing')->count())->toBe(4);
});

test('impossible de démarrer avec moins de deux équipes', function () {
    $tournament = tournamentWithTeams(1, courtCount: 2);

    expect(fn () => app(StartQualification::class)->handle($tournament))
        ->toThrow(TournamentWorkflowException::class);
});

test('démarre sans terrain : toutes les parties de la ronde sont en cours', function () {
    // Terrains optionnels (concours sur terrains non numérotés) : sans terrain,
    // toutes les parties de la ronde démarrent en parallèle, sans emplacement.
    $tournament = tournamentWithTeams(4, courtCount: 0);

    app(StartQualification::class)->handle($tournament);

    $tournament->refresh();

    $playing = $tournament->matches()
        ->where('phase', 'qualification')
        ->where('status', 'playing')
        ->get();

    expect($tournament->current_phase)->toBe('qualification')
        ->and($playing)->toHaveCount(2)
        ->and($playing->whereNotNull('court_id'))->toHaveCount(0);
});

test('impossible de démarrer deux fois les qualifications', function () {
    $tournament = tournamentWithTeams(8, courtCount: 4);
    app(StartQualification::class)->handle($tournament);

    expect(fn () => app(StartQualification::class)->handle($tournament->fresh()))
        ->toThrow(TournamentWorkflowException::class);
});

test('impossible de clôturer des qualifications non terminées', function () {
    $tournament = tournamentWithTeams(8, courtCount: 4);
    app(StartQualification::class)->handle($tournament);

    expect(fn () => app(CompleteQualification::class)->handle($tournament->fresh()))
        ->toThrow(TournamentWorkflowException::class);
});
