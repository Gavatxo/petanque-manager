<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Application\Tournament\Support\KnockoutEngineBuilder;
use App\Application\Tournament\Support\MatchSynchronizer;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Génère les tableaux à élimination directe (un par division A/B/C/D) : cadrages,
 * exempts et arbre complet sont matérialisés en base.
 */
final class GenerateFinalTables
{
    public function __construct(
        private readonly KnockoutEngineBuilder $engineBuilder,
        private readonly MatchSynchronizer $synchronizer,
    ) {}

    public function handle(Tournament $tournament): void
    {
        DB::transaction(function () use ($tournament): void {
            $divisions = $tournament->teams()
                ->whereNotNull('division')
                ->distinct()
                ->orderBy('division')
                ->pluck('division');

            foreach ($divisions as $division) {
                $count = $tournament->teams()->where('division', $division)->count();

                if ($count === 1) {
                    // Une seule équipe : championne d'office de sa division.
                    $tournament->teams()->where('division', $division)->update(['final_rank' => 1]);

                    continue;
                }

                $engine = $this->engineBuilder->build($tournament, (string) $division);
                $this->synchronizer->syncKnockout($tournament, (string) $division, $engine);
            }
        });
    }
}
