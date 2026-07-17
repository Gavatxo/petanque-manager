<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Models\Tournament;

/**
 * Après la saisie d'un résultat, matérialise les parties de la ronde suivante
 * si le moteur en a créées (une ronde vient de se clôturer).
 */
final class GenerateNextQualificationMatches
{
    public function __construct(
        private readonly CreateQualificationMatches $createMatches,
    ) {}

    public function handle(Tournament $tournament): void
    {
        $this->createMatches->handle($tournament);
    }
}
