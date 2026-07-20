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

test('sans terrain, toutes les parties de la ronde démarrent en parallèle', function () {
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 0);
    $engine->start();

    expect($engine->playingGames())->toHaveCount(4)
        ->and($engine->playingTeams())->toHaveCount(8)
        ->and($engine->pendingGames())->toHaveCount(0)
        ->and($engine->coveredTeams())->toHaveCount(0);

    foreach ($engine->playingGames() as $game) {
        expect($game->courtId())->toBeNull();
    }

    // Un score se saisit normalement, sans terrain à libérer.
    $game = $engine->playingGames()[0];
    $engine->recordResult($game->id->value, 13, 5);

    expect($engine->playingGames())->toHaveCount(3);
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

test('la partie suivante se remplit au fil de l’eau, sans attendre la fin de la ronde', function () {
    // 8 équipes, terrains à volonté : dès que deux gagnants ont fini, ils
    // s'affrontent — la ronde 2 démarre pendant que la ronde 1 tourne encore.
    $engine = TournamentSimulator::build(new TournamentConfiguration(3, 4), teamCount: 8, courtCount: 8);
    $engine->start();

    $win = function ($game) use ($engine): void {
        $seedA = $engine->team($game->teamA)->seed;
        $seedB = $engine->team($game->teamB)->seed;
        $engine->recordResult($game->id->value, $seedA > $seedB ? 13 : 7, $seedA > $seedB ? 7 : 13);
    };

    $round1 = $engine->playingGames();
    $win($round1[0]);
    $win($round1[1]);

    $round2 = array_values(array_filter($engine->games(), fn ($g): bool => $g->round === 2));
    $round1StillPlaying = array_filter($engine->playingGames(), fn ($g): bool => $g->round === 1);

    // La ronde 2 existe déjà alors que la ronde 1 n'est pas terminée.
    expect($round2)->not->toBeEmpty()
        ->and($round1StillPlaying)->not->toBeEmpty();

    // Chaque nouvelle partie oppose deux équipes de même bilan (gagnant vs
    // gagnant, perdant vs perdant).
    foreach ($round2 as $game) {
        $a = $engine->team($game->teamA);
        $b = $engine->team($game->teamB);
        expect($a->wins())->toBe($b->wins())
            ->and($a->losses())->toBe($b->losses());
    }
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
