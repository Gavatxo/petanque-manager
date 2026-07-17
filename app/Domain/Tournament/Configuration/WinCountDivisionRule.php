<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Configuration;

use App\Domain\Tournament\Enum\Division;

/**
 * Répartition par nombre de victoires : plus une équipe a gagné, plus son tableau
 * est haut.
 *
 *   index = (parties qualificatives − victoires), plafonné au dernier tableau.
 *
 * Exemple — 3 rondes, 3 tableaux (ABC) :
 *   3 victoires → A · 2 → B · 0-1 → C
 * Exemple — 4 rondes, 4 tableaux (ABCD) :
 *   4 → A · 3 → B · 2 → C · 0-1 → D
 */
final class WinCountDivisionRule implements DivisionRule
{
    public function divisionFor(int $wins, int $qualifyingRounds, int $divisionCount): Division
    {
        $index = $qualifyingRounds - $wins;
        $index = max(0, min($index, $divisionCount - 1));

        return Division::fromIndex($index);
    }
}
