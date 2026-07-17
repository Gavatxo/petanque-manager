<?php

namespace App\Enums;

enum CourtStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Occupied => 'Occupé',
            self::Disabled => 'Indisponible',
        };
    }
}
