<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Application\Tournament\Exception\TournamentWorkflowException;
use App\Application\Tournament\Support\SwissEngineBuilder;
use App\Domain\Tournament\Entity\Team as DomainTeam;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Clôture les qualifications : vérifie qu'elles sont terminées, puis affecte
 * chaque équipe à son tableau (division) avec son seed d'entrée, calculé depuis
 * le classement Swiss.
 */
final class CompleteQualification
{
    public function __construct(
        private readonly SwissEngineBuilder $engineBuilder,
    ) {}

    public function handle(Tournament $tournament): void
    {
        $engine = $this->engineBuilder->build($tournament);

        if (! $engine->isCompleted()) {
            throw TournamentWorkflowException::because('Les qualifications ne sont pas terminées.');
        }

        // Rang global (meilleur = 0) pour ordonner les équipes de chaque division.
        $globalRank = [];
        foreach ($engine->standings() as $rank => $team) {
            $globalRank[$team->id->value] = $rank;
        }

        DB::transaction(function () use ($engine, $globalRank): void {
            foreach ($engine->divisions() as $label => $teams) {
                usort(
                    $teams,
                    static fn (DomainTeam $a, DomainTeam $b): int => $globalRank[$a->id->value] <=> $globalRank[$b->id->value],
                );

                foreach ($teams as $index => $domainTeam) {
                    Team::query()->whereKey((int) $domainTeam->id->value)->update([
                        'division' => $label,
                        'division_seed' => $index + 1,
                    ]);
                }
            }
        });
    }
}
