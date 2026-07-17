<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Enum;

/**
 * Cycle de vie d'une partie (rencontre entre deux équipes).
 */
enum GameState: string
{
    /** Créée, équipes couvertes, en attente d'un terrain. */
    case Pending = 'pending';

    /** Sur un terrain. */
    case Playing = 'playing';

    /** Résultat saisi. */
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de terrain',
            self::Playing => 'En cours',
            self::Finished => 'Terminée',
        };
    }
}
