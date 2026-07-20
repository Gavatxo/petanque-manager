<?php

declare(strict_types=1);

use App\Domain\Tournament\Configuration\TournamentConfiguration;
use App\Domain\Tournament\Configuration\WinCountDivisionRule;
use App\Domain\Tournament\Entity\Game;
use App\Domain\Tournament\TournamentEngine;
use Tests\Support\TournamentSimulator;

/**
 * Matrice exigée : 8 / 32 / 64 / 128 équipes × 3 / 4 / 5 rondes.
 */
dataset('scénarios', function () {
    foreach ([8, 32, 64, 128] as $teams) {
        foreach ([3, 4, 5] as $rounds) {
            yield "{$teams} équipes / {$rounds} rondes" => [$teams, $rounds];
        }
    }
});

/**
 * Vérifie les invariants d'un concours mené à terme :
 *  - aucun blocage (le concours se termine) ;
 *  - aucune revanche ;
 *  - toutes les équipes ont joué le bon nombre de parties ;
 *  - tableaux A/B/C/D corrects selon la configuration.
 */
function assertTournamentIntegrity(TournamentEngine $engine, int $teamCount, int $rounds): void
{
    expect($engine->isCompleted())->toBeTrue();

    $rule = new WinCountDivisionRule;
    $divisionCount = $engine->configuration()->divisionCount;

    // 1. Chaque équipe a joué exactement `rounds` parties (exempts compris).
    //    L'appariement progressif peut exempter : un exempt compte comme une
    //    partie et une victoire, sans adversaire ajouté à l'historique.
    $totalByes = 0;
    foreach ($engine->teams() as $team) {
        $totalByes += $team->byes();
        $realGames = $rounds - $team->byes();

        // Bilan complet = rondes (un exempt compte comme une victoire) ; en
        // revanche l'historique d'adversaires ne compte que les parties réelles.
        expect($team->gamesPlayed())->toBe($rounds)
            ->and($team->wins() + $team->losses())->toBe($rounds);

        // 2. Aucune revanche : un adversaire distinct par partie réellement jouée.
        $history = $team->opponentHistory();
        expect(count($history))->toBe(count(array_unique($history)))
            ->and(count($history))->toBe($realGames);

        // 3. Tableau conforme à la règle (les exempts comptent comme victoires).
        expect($team->division())->toBe($rule->divisionFor($team->wins(), $rounds, $divisionCount));
    }

    // 4. Aucune paire d'équipes ne s'affronte deux fois sur l'ensemble des parties.
    $seenPairs = [];
    foreach ($engine->games() as $game) {
        $key = pairKey($game);
        expect($seenPairs)->not->toHaveKey($key);
        $seenPairs[$key] = true;
    }

    // 5. La répartition en tableaux couvre toutes les équipes.
    $divisions = $engine->divisions();
    $total = array_sum(array_map('count', $divisions));
    expect($total)->toBe($teamCount);

    // 6. Distribution binomiale exacte quand le Swiss reste « propre » : rondes ≤
    //    log2 N ET aucun exempt (sinon un exempt décale un bilan).
    if ((2 ** $rounds) <= $teamCount && $totalByes === 0) {
        expect(winHistogram($engine))->toBe(binomialHistogram($teamCount, $rounds));
    }
}

function pairKey(Game $game): string
{
    $ids = [$game->teamA->value, $game->teamB->value];
    sort($ids);

    return implode('-', $ids);
}

/**
 * @return array<int, int> nombre d'équipes par nombre de victoires
 */
function winHistogram(TournamentEngine $engine): array
{
    $histogram = [];
    foreach ($engine->teams() as $team) {
        $histogram[$team->wins()] = ($histogram[$team->wins()] ?? 0) + 1;
    }
    ksort($histogram);

    return $histogram;
}

/**
 * Distribution attendue d'un Swiss équilibré : C(rounds, k) × (N / 2^rounds).
 *
 * @return array<int, int>
 */
function binomialHistogram(int $teamCount, int $rounds): array
{
    $multiplier = intdiv($teamCount, 2 ** $rounds);
    $histogram = [];
    for ($wins = 0; $wins <= $rounds; $wins++) {
        $histogram[$wins] = binomial($rounds, $wins) * $multiplier;
    }

    return $histogram;
}

function binomial(int $n, int $k): int
{
    if ($k < 0 || $k > $n) {
        return 0;
    }

    $k = min($k, $n - $k);
    $result = 1;
    for ($i = 0; $i < $k; $i++) {
        $result = intdiv($result * ($n - $i), $i + 1);
    }

    return $result;
}

it('déroule un concours complet sans revanche ni blocage', function (int $teams, int $rounds) {
    $engine = TournamentSimulator::build(
        new TournamentConfiguration($rounds, divisionCount: 4),
        teamCount: $teams,
        courtCount: intdiv($teams, 2),
    );

    TournamentSimulator::run($engine, order: 'forward');

    assertTournamentIntegrity($engine, $teams, $rounds);
})->with('scénarios');

it('reste correct quand les résultats arrivent dans le désordre (asynchrone)', function (int $teams, int $rounds) {
    // Moins de terrains que de parties + saisie inversée : stress de l'asynchronisme.
    $engine = TournamentSimulator::build(
        new TournamentConfiguration($rounds, divisionCount: 4),
        teamCount: $teams,
        courtCount: max(1, intdiv($teams, 4)),
    );

    TournamentSimulator::run($engine, order: 'reverse');

    assertTournamentIntegrity($engine, $teams, $rounds);
})->with('scénarios');

it('respecte le nombre de tableaux configuré', function () {
    foreach ([1, 2, 3, 4] as $divisionCount) {
        $engine = TournamentSimulator::build(
            new TournamentConfiguration(qualifyingRounds: 4, divisionCount: $divisionCount),
            teamCount: 32,
            courtCount: 16,
        );

        TournamentSimulator::run($engine);

        $divisions = $engine->divisions();
        expect($divisions)->toHaveCount($divisionCount);

        // Chaque équipe est dans l'un des tableaux configurés.
        $labels = array_slice(['A', 'B', 'C', 'D'], 0, $divisionCount);
        foreach ($engine->teams() as $team) {
            expect($team->division()?->value)->toBeIn($labels);
        }
    }
});
