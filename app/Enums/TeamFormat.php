<?php

namespace App\Enums;

enum TeamFormat: string
{
    case TeteATete = 'tete_a_tete';
    case Doublette = 'doublette';
    case Triplette = 'triplette';

    /**
     * Number of players per team for this format.
     */
    public function teamSize(): int
    {
        return match ($this) {
            self::TeteATete => 1,
            self::Doublette => 2,
            self::Triplette => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::TeteATete => 'Tête-à-tête',
            self::Doublette => 'Doublette',
            self::Triplette => 'Triplette',
        };
    }
}
