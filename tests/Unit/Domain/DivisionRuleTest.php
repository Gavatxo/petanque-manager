<?php

declare(strict_types=1);

use App\Domain\Tournament\Configuration\WinCountDivisionRule;
use App\Domain\Tournament\Enum\Division;

$rule = new WinCountDivisionRule;

test('3 rondes / 4 tableaux répartit chaque bilan dans son tableau', function () use ($rule) {
    expect($rule->divisionFor(3, 3, 4))->toBe(Division::A)
        ->and($rule->divisionFor(2, 3, 4))->toBe(Division::B)
        ->and($rule->divisionFor(1, 3, 4))->toBe(Division::C)
        ->and($rule->divisionFor(0, 3, 4))->toBe(Division::D);
});

test('4 rondes / 4 tableaux', function () use ($rule) {
    expect($rule->divisionFor(4, 4, 4))->toBe(Division::A)
        ->and($rule->divisionFor(3, 4, 4))->toBe(Division::B)
        ->and($rule->divisionFor(2, 4, 4))->toBe(Division::C)
        ->and($rule->divisionFor(1, 4, 4))->toBe(Division::D)
        ->and($rule->divisionFor(0, 4, 4))->toBe(Division::D);
});

test('le nombre de tableaux plafonne l’index (AB, ABC)', function () use ($rule) {
    // 2 tableaux : seul le sans-faute est en A, le reste en B.
    expect($rule->divisionFor(3, 3, 2))->toBe(Division::A)
        ->and($rule->divisionFor(2, 3, 2))->toBe(Division::B)
        ->and($rule->divisionFor(0, 3, 2))->toBe(Division::B);

    // 3 tableaux.
    expect($rule->divisionFor(3, 3, 3))->toBe(Division::A)
        ->and($rule->divisionFor(2, 3, 3))->toBe(Division::B)
        ->and($rule->divisionFor(1, 3, 3))->toBe(Division::C)
        ->and($rule->divisionFor(0, 3, 3))->toBe(Division::C);
});
