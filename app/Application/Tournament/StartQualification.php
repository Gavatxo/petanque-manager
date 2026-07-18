<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Application\Tournament\Exception\TournamentWorkflowException;
use App\Enums\TournamentStatus;
use App\Events\TournamentUpdated;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Lance la phase de qualification : vérifie les prérequis, positionne le
 * concours en « qualification » et crée les parties de la première ronde.
 */
final class StartQualification
{
    public function __construct(
        private readonly CreateQualificationMatches $createMatches,
    ) {}

    public function handle(Tournament $tournament): void
    {
        if ($tournament->current_phase !== null) {
            throw TournamentWorkflowException::because('Le concours a déjà démarré.');
        }

        if ($tournament->teams()->count() < 2) {
            throw TournamentWorkflowException::because('Au moins deux équipes sont nécessaires.');
        }

        // Les terrains sont optionnels (concours sur terrains non numérotés) :
        // sans terrain, les parties se jouent sans emplacement attribué.

        DB::transaction(function () use ($tournament): void {
            $tournament->update([
                'status' => TournamentStatus::Running,
                'current_phase' => 'qualification',
                'started_at' => now(),
            ]);

            $this->createMatches->handle($tournament->fresh());
        });

        TournamentUpdated::dispatch($tournament->id);
    }
}
