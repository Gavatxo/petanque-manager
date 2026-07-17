<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Configuration;

use App\Domain\Tournament\Enum\Division;

/**
 * Règle de répartition des équipes dans les tableaux à l'issue des qualifications.
 */
interface DivisionRule
{
    public function divisionFor(int $wins, int $qualifyingRounds, int $divisionCount): Division;
}
