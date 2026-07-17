<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Knockout\Enum;

/**
 * Cycle de vie d'une partie d'un tableau à élimination directe.
 */
enum KnockoutGameState: string
{
    /** En attente : les deux adversaires ne sont pas encore connus. */
    case Awaiting = 'awaiting';

    /** Prête : les deux adversaires sont connus, la partie peut se jouer. */
    case Ready = 'ready';

    /** Terminée (résultat saisi ou exempt). */
    case Finished = 'finished';
}
