<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Pairing;

use App\Domain\Tournament\ValueObject\TeamId;

/**
 * Résultat d'une passe d'appariement : les paires formées et, le cas échéant,
 * l'équipe exempte (nombre impair d'équipes).
 */
final readonly class PairingResult
{
    /**
     * @param  list<PairingPair>  $pairs
     */
    public function __construct(
        public array $pairs,
        public ?TeamId $byeTeam = null,
    ) {}
}
