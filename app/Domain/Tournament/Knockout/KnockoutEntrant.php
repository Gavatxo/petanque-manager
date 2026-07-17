<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Knockout;

use App\Domain\Tournament\ValueObject\TeamId;

/**
 * Une équipe engagée dans un tableau à élimination directe.
 *
 * Le seed (plus petit = mieux classé) provient du classement des qualifications
 * et détermine son placement dans le tableau.
 */
final readonly class KnockoutEntrant
{
    public function __construct(
        public TeamId $id,
        public string $name,
        public int $seed,
    ) {}
}
