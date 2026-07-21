<?php

declare(strict_types=1);

use App\Domain\Tournament\Configuration\DivisionPlanner;

test('suggère des tailles en puissances de 2, décroissantes, le reste au dernier tableau', function () {
    // 21 équipes, 3 tableaux → A=8, B=8, reste C=5.
    expect(DivisionPlanner::suggestUpperSizes(21, 3))->toBe([8, 8]);
    expect(DivisionPlanner::fullSizes(21, 3, [8, 8]))->toBe([8, 8, 5]);

    // 16 équipes, 3 tableaux → A=8, B=4, reste C=4 (aucun qualifié d'office).
    expect(DivisionPlanner::suggestUpperSizes(16, 3))->toBe([8, 4]);
    expect(DivisionPlanner::fullSizes(16, 3, [8, 4]))->toBe([8, 4, 4]);

    // 32 équipes, 4 tableaux → 8 / 8 / 8 / 8.
    expect(DivisionPlanner::suggestUpperSizes(32, 4))->toBe([8, 8, 8]);

    // 5 équipes, 2 tableaux → A=4 (plein), reste B=1.
    expect(DivisionPlanner::suggestUpperSizes(5, 2))->toBe([4]);
    expect(DivisionPlanner::fullSizes(5, 2, [4]))->toBe([4, 1]);

    // 1 tableau → tout le monde dans A (aucune taille haute).
    expect(DivisionPlanner::suggestUpperSizes(21, 1))->toBe([]);
    expect(DivisionPlanner::fullSizes(21, 1, []))->toBe([21]);
});

test('les tailles suggérées des tableaux du haut sont des puissances de 2', function () {
    foreach ([[21, 3], [16, 3], [32, 4], [12, 2], [8, 3], [64, 4], [40, 3]] as [$teams, $k]) {
        foreach (DivisionPlanner::suggestUpperSizes($teams, $k) as $size) {
            expect(DivisionPlanner::isPowerOfTwo($size))->toBeTrue();
        }
    }
});

test('répartit les équipes classées dans les tableaux avec un seed d’entrée', function () {
    $ranked = [10, 20, 30, 40, 50]; // identifiants classés (meilleur d'abord)

    // A=2, reste B=3.
    $plan = DivisionPlanner::plan($ranked, [2], 2);

    expect($plan[10])->toBe(['division' => 'A', 'seed' => 1])
        ->and($plan[20])->toBe(['division' => 'A', 'seed' => 2])
        ->and($plan[30])->toBe(['division' => 'B', 'seed' => 1])
        ->and($plan[40])->toBe(['division' => 'B', 'seed' => 2])
        ->and($plan[50])->toBe(['division' => 'B', 'seed' => 3]);
});

test('le dernier tableau absorbe le reste même si les tailles hautes dépassent', function () {
    $ranked = [1, 2, 3];
    // A demandé à 8 mais seulement 3 équipes : A prend 3, B (reste) vide.
    $plan = DivisionPlanner::plan($ranked, [8], 2);

    expect($plan[1]['division'])->toBe('A')
        ->and($plan[2]['division'])->toBe('A')
        ->and($plan[3]['division'])->toBe('A');
});
