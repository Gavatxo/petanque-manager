<?php

declare(strict_types=1);

namespace App\Application\Tournament\Exception;

use RuntimeException;

/**
 * Erreur de séquencement du déroulé d'un concours au niveau applicatif
 * (ex. démarrer les finales avant la fin des qualifications).
 */
final class TournamentWorkflowException extends RuntimeException
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
