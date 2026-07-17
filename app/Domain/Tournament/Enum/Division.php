<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Enum;

use App\Domain\Tournament\Exception\InvalidTournamentStateException;

/**
 * Tableau / division d'un concours : A (principal), B, C, D (consolantes).
 */
enum Division: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';

    public static function fromIndex(int $index): self
    {
        return match ($index) {
            0 => self::A,
            1 => self::B,
            2 => self::C,
            3 => self::D,
            default => throw InvalidTournamentStateException::because(
                "Index de tableau invalide : {$index} (0 à 3 attendus).",
            ),
        };
    }

    public function index(): int
    {
        return match ($this) {
            self::A => 0,
            self::B => 1,
            self::C => 2,
            self::D => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::A => 'Tableau A (principal)',
            self::B => 'Tableau B',
            self::C => 'Tableau C',
            self::D => 'Tableau D',
        };
    }
}
