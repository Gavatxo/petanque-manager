<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Application\Tournament\Support\MatchSynchronizer;
use App\Application\Tournament\Support\SwissEngineBuilder;
use App\Models\Tournament;

/**
 * Matérialise en base les parties de qualification que le moteur Swiss vient de
 * créer (ronde courante). Idempotent.
 */
final class CreateQualificationMatches
{
    public function __construct(
        private readonly SwissEngineBuilder $engineBuilder,
        private readonly MatchSynchronizer $synchronizer,
    ) {}

    public function handle(Tournament $tournament): void
    {
        $engine = $this->engineBuilder->build($tournament);
        $this->synchronizer->syncQualification($tournament, $engine);
    }
}
