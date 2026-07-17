<?php

declare(strict_types=1);

use App\Domain\Tournament\Configuration\TournamentConfiguration;
use App\Domain\Tournament\Enum\TeamState;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\TournamentEngine;
use Tests\Support\TournamentSimulator;

test('le démarrage apparie la première ronde et occupe les terrains', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 4);
    $engine->start();

    expect($engine->currentRound())->toBe(1)
        ->and($engine->playingGames())->toHaveCount(4)
        ->and($engine->playingTeams())->toHaveCount(8)
        ->and($engine->availableCourts())->toHaveCount(0)
        ->and($engine->coveredTeams())->toHaveCount(0);
});

test('avec moins de terrains que de parties, des équipes restent couvertes', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 2);
    $engine->start();

    // 4 parties : 2 sur terrain, 2 couvertes en attente d'un terrain.
    expect($engine->playingGames())->toHaveCount(2)
        ->and($engine->pendingGames())->toHaveCount(2)
        ->and($engine->playingTeams())->toHaveCount(4)
        ->and($engine->coveredTeams())->toHaveCount(4)
        ->and($engine->availableCourts())->toHaveCount(0);
});

test('un résultat libère un terrain qui couvre aussitôt une partie en attente', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 2);
    $engine->start();

    $first = $engine->playingGames()[0];
    $engine->recordResult($first->id->value, 13, 7);

    // La partie terminée libère un terrain -> une partie en attente passe en jeu.
    expect($engine->playingGames())->toHaveCount(2)
        ->and($engine->pendingGames())->toHaveCount(1)
        ->and($engine->availableTeams())->toHaveCount(2)   // les deux équipes qui viennent de finir
        ->and($engine->playingTeams())->toHaveCount(4);
});

test('une équipe qui finit avant les autres passe en attente de la ronde suivante', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 4);
    $engine->start();

    $game = $engine->playingGames()[0];
    $engine->recordResult($game->id->value, 13, 7);

    // Ronde pas encore terminée : les deux équipes sont disponibles, en attente.
    expect($engine->currentRound())->toBe(1)
        ->and($engine->availableTeams())->toHaveCount(2)
        ->and($engine->team($game->teamA)->state())->toBe(TeamState::Available);
});

test('un nombre impair d’équipes en exempte une (état en attente)', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(2, 3), teamCount: 5, courtCount: 3);
    $engine->start();

    expect($engine->waitingTeams())->toHaveCount(1)
        ->and($engine->playingTeams())->toHaveCount(4)
        ->and($engine->playingGames())->toHaveCount(2);
});

test('un concours complet répartit correctement les tableaux (8 équipes, 3 rondes)', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 4);
    TournamentSimulator::run($engine);

    expect($engine->isCompleted())->toBeTrue();

    foreach ($engine->teams() as $team) {
        expect($team->isQualified())->toBeTrue()
            ->and($team->gamesPlayed())->toBe(3);
    }

    $divisions = $engine->divisions();

    // Distribution binomiale d'un Swiss à 8 équipes sur 3 rondes : 1 / 3 / 3 / 1.
    expect(array_keys($divisions))->toBe(['A', 'B', 'C', 'D'])
        ->and($divisions['A'])->toHaveCount(1)
        ->and($divisions['B'])->toHaveCount(3)
        ->and($divisions['C'])->toHaveCount(3)
        ->and($divisions['D'])->toHaveCount(1);
});

test('un concours à nombre impair se termine malgré les exempts', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 7, courtCount: 4);
    TournamentSimulator::run($engine);

    expect($engine->isCompleted())->toBeTrue();

    foreach ($engine->teams() as $team) {
        expect($team->gamesPlayed())->toBe(3)
            ->and(count($team->opponentHistory()))->toBe(count(array_unique($team->opponentHistory())));
    }
});

test('impossible d’inscrire une équipe après le démarrage', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 4);
    $engine->start();

    expect(fn () => $engine->registerTeam('99', 'Retardataire'))
        ->toThrow(InvalidTournamentStateException::class);
});

test('saisir un résultat sur une partie inconnue échoue', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 4);
    $engine->start();

    expect(fn () => $engine->recordResult('inconnu', 13, 7))
        ->toThrow(InvalidTournamentStateException::class);
});

test('un score nul (égalité) est refusé', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 4);
    $engine->start();

    $game = $engine->playingGames()[0];

    expect(fn () => $engine->recordResult($game->id->value, 13, 13))
        ->toThrow(InvalidTournamentStateException::class);
});

test('un concours a besoin d’équipes et de terrains pour démarrer', function () {
    $engine = new TournamentEngine(new TournamentConfiguration(3, 4));
    $engine->registerTeam('1', 'Seule');

    expect(fn () => $engine->start())->toThrow(InvalidTournamentStateException::class);
});
