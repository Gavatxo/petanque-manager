<?php

declare(strict_types=1);

use App\Domain\Tournament\Enum\Division;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\Knockout\KnockoutEngine;
use App\Domain\Tournament\Knockout\KnockoutEntrant;
use App\Domain\Tournament\ValueObject\TeamId;
use Tests\Support\KnockoutSimulator;

test('dimensionne le tableau, les exempts et le cadrage', function (
    int $entrants,
    int $bracketSize,
    int $rounds,
    int $byes,
    int $cadrage,
) {
    $engine = KnockoutSimulator::build($entrants);

    expect($engine->bracketSize())->toBe($bracketSize)
        ->and($engine->totalRounds())->toBe($rounds)
        ->and($engine->byeCount())->toBe($byes)
        ->and($engine->cadrageGameCount())->toBe($cadrage)
        ->and($engine->hasCadrage())->toBe($cadrage > 0);
})->with([
    'puissance de 2 : 8' => [8, 8, 3, 0, 0],
    'non puissance de 2 : 12' => [12, 16, 4, 4, 4],
    'puissance de 2 : 16' => [16, 16, 4, 0, 0],
    'non puissance de 2 : 23' => [23, 32, 5, 9, 7],
    'puissance de 2 : 32' => [32, 32, 5, 0, 0],
]);

test('les exempts sont propagés dès la construction (seul le cadrage est à jouer)', function () {
    $engine = KnockoutSimulator::build(12);

    // 4 parties de cadrage prêtes, les 4 exempts déjà propagés dans les quarts.
    expect($engine->readyGames())->toHaveCount(4);

    // Chaque partie du 2e tour a déjà un exempt en attente d'un vainqueur de cadrage.
    foreach ($engine->gamesInRound(2) as $game) {
        expect($game->hasExactlyOneTeam())->toBeTrue();
    }
});

test('un tableau à une seule équipe la sacre d’office', function () {
    $engine = new KnockoutEngine([new KnockoutEntrant(TeamId::of('t1'), 'Solo', 1)]);

    expect($engine->isComplete())->toBeTrue()
        ->and($engine->champion()?->value)->toBe('t1')
        ->and($engine->finalRanking())->toHaveCount(1);
});

test('le vainqueur est propagé automatiquement au tour suivant', function () {
    $engine = KnockoutSimulator::build(8);

    $game = $engine->gamesInRound(1)[0]; // seeds 1 vs 8
    $engine->recordResult($game->id, 13, 7);

    // Le vainqueur (t1) occupe l'emplacement A de la partie parente.
    expect($engine->gamesInRound(2)[0]->slotA()?->value)->toBe($game->winner()?->value);
});

test('porte l’étiquette de tableau (A/B/C/D)', function () {
    $engine = KnockoutSimulator::build(16, Division::B);

    expect($engine->division())->toBe(Division::B);
});

test('nomme les tours', function () {
    $withCadrage = KnockoutSimulator::build(12);
    expect($withCadrage->roundLabel(1))->toBe('Cadrage')
        ->and($withCadrage->roundLabel(4))->toBe('Finale');

    $clean = KnockoutSimulator::build(8);
    expect($clean->roundLabel(1))->toBe('Quarts de finale')
        ->and($clean->roundLabel(2))->toBe('Demi-finales')
        ->and($clean->roundLabel(3))->toBe('Finale');
});

test('refuse un tableau vide', function () {
    expect(fn () => new KnockoutEngine([]))
        ->toThrow(InvalidTournamentStateException::class);
});

test('refuse une saisie sur une partie non prête', function () {
    $engine = KnockoutSimulator::build(8);
    $awaiting = $engine->gamesInRound(2)[0]; // adversaires encore inconnus

    expect(fn () => $engine->recordResult($awaiting->id, 13, 7))
        ->toThrow(InvalidTournamentStateException::class);
});
