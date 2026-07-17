<?php

declare(strict_types=1);

use App\Domain\Tournament\Enum\Division;
use App\Domain\Tournament\Knockout\KnockoutGame;
use Tests\Support\KnockoutSimulator;

/**
 * Matrice exigée : 8 / 12 / 16 / 23 / 32 équipes.
 */
dataset('effectifs', [
    '8 équipes' => [8],
    '12 équipes (cadrage)' => [12],
    '16 équipes' => [16],
    '23 équipes (cadrage)' => [23],
    '32 équipes' => [32],
]);

it('mène un tableau à son terme et classe toutes les équipes', function (int $entrants) {
    $engine = KnockoutSimulator::build($entrants);
    $played = KnockoutSimulator::run($engine);

    // 1. Le tableau se termine (aucun blocage de propagation).
    expect($engine->isComplete())->toBeTrue();

    // 2. Toutes les parties sont terminées.
    foreach ($engine->games() as $game) {
        expect($game->isFinished())->toBeTrue();
    }

    // 3. Nombre de parties jouées = parties totales − exempts.
    $walkovers = count(array_filter(
        $engine->games(),
        static fn (KnockoutGame $game): bool => $game->isWalkover(),
    ));
    expect($walkovers)->toBe($engine->byeCount())
        ->and($played)->toBe(count($engine->games()) - $walkovers);

    // 4. Sans surprise (meilleur seed toujours vainqueur), le classement final
    //    est exactement l'ordre des seeds.
    $ranking = $engine->finalRanking();
    expect($ranking)->toHaveCount($entrants);

    foreach ($ranking as $index => $rankedTeam) {
        expect($rankedTeam->position)->toBe($index + 1)
            ->and($rankedTeam->seed)->toBe($index + 1)
            ->and($rankedTeam->teamId->value)->toBe('t'.($index + 1));
    }

    // 5. Championne = meilleur seed ; une seule championne.
    expect($engine->champion()?->value)->toBe('t1')
        ->and($ranking[0]->isChampion)->toBeTrue()
        ->and(array_filter($ranking, static fn ($team): bool => $team->isChampion))->toHaveCount(1);
})->with('effectifs');

it('gère un tableau indépendant par division A/B/C/D', function () {
    // Chaque division a son propre tableau, de taille libre (dont non puissances de 2).
    $brackets = [
        Division::A->value => KnockoutSimulator::build(16, Division::A),
        Division::B->value => KnockoutSimulator::build(12, Division::B),
        Division::C->value => KnockoutSimulator::build(23, Division::C),
        Division::D->value => KnockoutSimulator::build(8, Division::D),
    ];

    foreach ($brackets as $label => $engine) {
        KnockoutSimulator::run($engine);

        expect($engine->division()?->value)->toBe($label)
            ->and($engine->isComplete())->toBeTrue()
            ->and($engine->champion()?->value)->toBe('t1');
    }
});
