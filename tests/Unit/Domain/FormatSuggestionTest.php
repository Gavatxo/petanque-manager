<?php

declare(strict_types=1);

use App\Domain\Tournament\Configuration\FormatSuggestion;

test('les petits concours restent sur un seul tableau', function () {
    $s = FormatSuggestion::forTeamCount(6);

    expect($s->tableauxCount)->toBe(1)
        ->and($s->qualifyingRounds)->toBe(3)
        ->and($s->pointsTarget)->toBe(13);
});

test('le nombre de tableaux croît avec le nombre d’équipes', function () {
    expect(FormatSuggestion::forTeamCount(8)->tableauxCount)->toBe(2)
        ->and(FormatSuggestion::forTeamCount(16)->tableauxCount)->toBe(3)
        ->and(FormatSuggestion::forTeamCount(32)->tableauxCount)->toBe(4)
        ->and(FormatSuggestion::forTeamCount(120)->tableauxCount)->toBe(4);
});

test('il y a toujours assez de rondes pour peupler chaque tableau', function () {
    foreach ([2, 7, 8, 15, 16, 31, 32, 64, 200] as $teams) {
        $s = FormatSuggestion::forTeamCount($teams);

        // Une équipe qui perd tout doit pouvoir atteindre le dernier tableau
        // (index = rondes − victoires, plafonné à tableaux − 1).
        expect($s->qualifyingRounds)->toBeGreaterThanOrEqual($s->tableauxCount - 1);
    }
});

test('toArray expose les clés attendues par le front', function () {
    expect(FormatSuggestion::forTeamCount(16)->toArray())->toBe([
        'qualifying_rounds' => 4,
        'tableaux_count' => 3,
        'points_target' => 13,
    ]);
});
