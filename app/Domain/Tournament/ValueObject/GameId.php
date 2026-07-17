<?php

declare(strict_types=1);

namespace App\Domain\Tournament\ValueObject;

use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use Stringable;

final readonly class GameId implements Stringable
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw InvalidTournamentStateException::because('Un identifiant de partie ne peut pas être vide.');
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
