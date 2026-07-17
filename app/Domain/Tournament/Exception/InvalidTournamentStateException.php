<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Exception;

/**
 * Levée lorsqu'une opération est demandée dans un état incohérent
 * (ex. saisir un résultat sur une partie qui n'est pas en cours).
 */
final class InvalidTournamentStateException extends TournamentDomainException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
