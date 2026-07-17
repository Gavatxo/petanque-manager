<?php

declare(strict_types=1);

namespace App\Domain\Tournament\ValueObject;

use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use Stringable;

/**
 * Identité d'une équipe. Value object immuable pour éviter de mélanger les
 * identifiants (équipe, terrain, partie) dans le moteur.
 */
final readonly class TeamId implements Stringable
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw InvalidTournamentStateException::because('Un identifiant d’équipe ne peut pas être vide.');
        }
    }

    public static function of(string|int $value): self
    {
        return new self((string) $value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
