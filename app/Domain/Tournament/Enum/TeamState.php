<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Enum;

/**
 * Cycle de vie d'une équipe pendant un concours.
 */
enum TeamState: string
{
    /** Inscrite, le concours n'a pas encore commencé pour elle. */
    case Idle = 'idle';

    /** Disponible : a terminé sa partie (ou vient d'entrer), prête à être appariée. */
    case Available = 'available';

    /** En attente : aucun adversaire jamais rencontré n'était disponible (ex. exempt). */
    case Waiting = 'waiting';

    /** Couverte : un adversaire lui a été attribué, en attente d'un terrain. */
    case Covered = 'covered';

    /** En jeu : sur un terrain. */
    case Playing = 'playing';

    /** Qualifiée : parties qualificatives terminées, affectée à un tableau. */
    case Qualified = 'qualified';

    public function label(): string
    {
        return match ($this) {
            self::Idle => 'Inactive',
            self::Available => 'Disponible',
            self::Waiting => 'En attente',
            self::Covered => 'Couverte',
            self::Playing => 'En jeu',
            self::Qualified => 'Qualifiée',
        };
    }
}
