<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Pairing;

use App\Domain\Tournament\ValueObject\TeamId;

final readonly class PairingPair
{
    public function __construct(
        public TeamId $teamA,
        public TeamId $teamB,
    ) {}
}
