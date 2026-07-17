<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Tournament\Enum\Division;
use App\Domain\Tournament\Knockout\KnockoutEngine;
use App\Domain\Tournament\Knockout\KnockoutEntrant;
use App\Domain\Tournament\Knockout\KnockoutGame;
use App\Domain\Tournament\ValueObject\TeamId;
use RuntimeException;

/**
 * Aide de test : construit et déroule un tableau à élimination directe.
 *
 * Résultats déterministes : le meilleur seed (plus petit numéro) gagne toujours,
 * ce qui doit produire un classement final strictement égal à l'ordre des seeds.
 */
final class KnockoutSimulator
{
    public static function build(int $entrantCount, ?Division $division = null): KnockoutEngine
    {
        $entrants = [];
        for ($i = 1; $i <= $entrantCount; $i++) {
            $entrants[] = new KnockoutEntrant(TeamId::of("t{$i}"), "Équipe {$i}", $i);
        }

        return new KnockoutEngine($entrants, $division);
    }

    /**
     * Déroule le tableau jusqu'au sacre. Le meilleur seed gagne toujours.
     *
     * @return int nombre de parties réellement jouées (hors exempts)
     */
    public static function run(KnockoutEngine $engine): int
    {
        $played = 0;
        $guard = 0;

        while (! $engine->isComplete()) {
            $ready = $engine->readyGames();

            if ($ready === []) {
                throw new RuntimeException('Blocage : aucune partie prête alors que le tableau n’est pas terminé.');
            }

            foreach ($ready as $game) {
                self::recordBestSeedWins($engine, $game);
                $played++;
            }

            if (++$guard > 100_000) {
                throw new RuntimeException('Simulation emballée : arrêt de sécurité.');
            }
        }

        return $played;
    }

    private static function recordBestSeedWins(KnockoutEngine $engine, KnockoutGame $game): void
    {
        $slotA = $game->slotA();
        $slotB = $game->slotB();

        if ($slotA === null || $slotB === null) {
            throw new RuntimeException('Partie prête sans deux adversaires.');
        }

        $seedA = $engine->entrant($slotA)->seed;
        $seedB = $engine->entrant($slotB)->seed;

        // Le meilleur seed (plus petit) l'emporte.
        if ($seedA < $seedB) {
            $engine->recordResult($game->id, 13, 7);
        } else {
            $engine->recordResult($game->id, 7, 13);
        }
    }
}
