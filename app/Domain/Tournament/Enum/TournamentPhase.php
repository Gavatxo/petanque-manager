<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Enum;

enum TournamentPhase: string
{
    /** Pas encore démarré. */
    case Setup = 'setup';

    /** Parties qualificatives en cours. */
    case Qualification = 'qualification';

    /** Qualifications terminées, équipes réparties dans les tableaux. */
    case Completed = 'completed';
}
