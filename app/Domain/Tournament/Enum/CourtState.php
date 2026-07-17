<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Enum;

enum CourtState: string
{
    case Available = 'available';
    case Occupied = 'occupied';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Occupied => 'Occupé',
        };
    }
}
