<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Configuration;

use App\Domain\Tournament\Exception\InvalidTournamentStateException;

/**
 * Paramètres de format d'un concours libre. Immuable.
 */
final readonly class TournamentConfiguration
{
    public DivisionRule $divisionRule;

    public function __construct(
        public int $qualifyingRounds,
        public int $divisionCount,
        public int $pointsTarget = 13,
        ?DivisionRule $divisionRule = null,
    ) {
        if ($qualifyingRounds < 1) {
            throw InvalidTournamentStateException::because('Le nombre de parties qualificatives doit être au moins 1.');
        }

        if ($divisionCount < 1 || $divisionCount > 4) {
            throw InvalidTournamentStateException::because('Le nombre de tableaux doit être compris entre 1 et 4.');
        }

        if ($pointsTarget < 1) {
            throw InvalidTournamentStateException::because('Le score cible doit être au moins 1.');
        }

        $this->divisionRule = $divisionRule ?? new WinCountDivisionRule;
    }
}
