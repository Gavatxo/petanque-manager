<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Knockout;

use App\Domain\Tournament\ValueObject\TeamId;

/**
 * Une place au classement final d'un tableau.
 */
final readonly class RankedTeam
{
    public function __construct(
        public int $position,
        public TeamId $teamId,
        public string $name,
        public int $seed,
        public bool $isChampion,
        public ?int $eliminatedInRound,
    ) {}
}
