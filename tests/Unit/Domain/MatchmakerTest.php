<?php

declare(strict_types=1);

use App\Domain\Tournament\Entity\Team;
use App\Domain\Tournament\Exception\PairingDeadlockException;
use App\Domain\Tournament\Pairing\Matchmaker;
use App\Domain\Tournament\Pairing\PairingPair;
use App\Domain\Tournament\ValueObject\TeamId;

/**
 * @param  list<string>  $wonAgainst
 * @param  list<string>  $lostAgainst
 */
function pooledTeam(string $id, int $seed, array $wonAgainst = [], array $lostAgainst = []): Team
{
    $team = new Team(TeamId::of($id), "Équipe {$id}", $seed);

    foreach ($wonAgainst as $opponent) {
        $team->applyResult(won: true, opponent: TeamId::of($opponent));
    }

    foreach ($lostAgainst as $opponent) {
        $team->applyResult(won: false, opponent: TeamId::of($opponent));
    }

    $team->markAvailable();

    return $team;
}

/**
 * @param  list<PairingPair>  $pairs
 * @return list<array{0: string, 1: string}> paires normalisées (ids triés)
 */
function normalizePairs(array $pairs): array
{
    return array_map(function (PairingPair $pair): array {
        $ids = [$pair->teamA->value, $pair->teamB->value];
        sort($ids);

        return $ids;
    }, $pairs);
}

test('apparie des équipes de même bilan ensemble', function () {
    // A, B ont 2 victoires (contre des adversaires externes) ; C, D en ont 0.
    $result = (new Matchmaker)->pair([
        pooledTeam('A', 0, ['x1', 'x2']),
        pooledTeam('B', 1, ['y1', 'y2']),
        pooledTeam('C', 2),
        pooledTeam('D', 3),
    ]);

    $pairs = normalizePairs($result->pairs);

    expect($result->byeTeam)->toBeNull()
        ->and($pairs)->toHaveCount(2)
        ->and($pairs)->toContain(['A', 'B'])
        ->and($pairs)->toContain(['C', 'D']);
});

test('ne rejoue jamais une revanche', function () {
    // 1 et 2 se sont déjà rencontrés.
    $result = (new Matchmaker)->pair([
        pooledTeam('1', 0, lostAgainst: ['2']),
        pooledTeam('2', 1, ['1']),
        pooledTeam('3', 2),
        pooledTeam('4', 3),
    ]);

    $pairs = normalizePairs($result->pairs);

    expect($pairs)->toHaveCount(2)
        ->and($pairs)->not->toContain(['1', '2']);
});

test('exempte une équipe si le nombre est impair', function () {
    $result = (new Matchmaker)->pair([
        pooledTeam('1', 0),
        pooledTeam('2', 1),
        pooledTeam('3', 2),
    ]);

    expect($result->byeTeam)->not->toBeNull()
        ->and($result->pairs)->toHaveCount(1);
});

test('trouve un appariement même avec des exclusions denses', function () {
    // 6 équipes ; chacune a déjà joué contre ses deux voisines de bilan.
    $result = (new Matchmaker)->pair([
        pooledTeam('1', 0, lostAgainst: ['2', '3']),
        pooledTeam('2', 1, ['1'], ['3']),
        pooledTeam('3', 2, ['1', '2']),
        pooledTeam('4', 3, lostAgainst: ['5', '6']),
        pooledTeam('5', 4, ['4'], ['6']),
        pooledTeam('6', 5, ['4', '5']),
    ]);

    $pairs = normalizePairs($result->pairs);

    expect($pairs)->toHaveCount(3);

    // Aucune paire ne doit être une revanche.
    $forbidden = [['1', '2'], ['1', '3'], ['2', '3'], ['4', '5'], ['4', '6'], ['5', '6']];
    foreach ($forbidden as $pair) {
        expect($pairs)->not->toContain($pair);
    }
});

test('lève un blocage quand aucun appariement sans revanche n’existe', function () {
    // Deux équipes qui se sont déjà affrontées : impossible sans revanche.
    expect(fn () => (new Matchmaker)->pair([
        pooledTeam('1', 0, ['2']),
        pooledTeam('2', 1, lostAgainst: ['1']),
    ]))->toThrow(PairingDeadlockException::class);
});
