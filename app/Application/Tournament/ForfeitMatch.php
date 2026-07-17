<?php

declare(strict_types=1);

namespace App\Application\Tournament;

use App\Application\Tournament\Exception\TournamentWorkflowException;
use App\Models\Matchup;

/**
 * Déclare le forfait d'une équipe sur une partie : l'adversaire l'emporte
 * (score plein à 0), et la partie est marquée comme gagnée par forfait.
 */
final class ForfeitMatch
{
    public function __construct(
        private readonly RecordMatchResult $recordResult,
    ) {}

    public function handle(Matchup $match, int $forfeitingTeamId): void
    {
        if (! in_array($match->status, ['playing', 'ready'], true)) {
            throw TournamentWorkflowException::because("La partie {$match->id} n'est pas en cours.");
        }

        if (! in_array($forfeitingTeamId, [$match->team_a_id, $match->team_b_id], true)) {
            throw TournamentWorkflowException::because('Cette équipe ne joue pas cette partie.');
        }

        $target = $match->tournament->points_target;
        $teamAWins = $forfeitingTeamId === $match->team_b_id;

        $this->recordResult->handle(
            $match,
            $teamAWins ? $target : 0,
            $teamAWins ? 0 : $target,
        );

        $match->refresh();
        $match->update(['is_forfeit' => true]);
    }
}
