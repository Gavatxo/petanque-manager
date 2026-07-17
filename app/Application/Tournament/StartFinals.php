<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Events\TournamentUpdated;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Bascule le concours en phase finale : clôture les qualifications (affectation
 * des divisions) puis génère les tableaux à élimination directe.
 */
final class StartFinals
{
    public function __construct(
        private readonly CompleteQualification $completeQualification,
        private readonly GenerateFinalTables $generateFinalTables,
    ) {}

    public function handle(Tournament $tournament): void
    {
        DB::transaction(function () use ($tournament): void {
            $this->completeQualification->handle($tournament);
            $this->generateFinalTables->handle($tournament->fresh());

            $tournament->update(['current_phase' => 'finals']);
        });

        TournamentUpdated::dispatch($tournament->id);
    }
}
