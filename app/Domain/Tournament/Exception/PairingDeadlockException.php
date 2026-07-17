<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Exception;

/**
 * Levée quand le moteur ne parvient à former aucun appariement valide pour les
 * équipes en attente : plus aucun adversaire jamais rencontré n'est disponible.
 *
 * C'est le signal explicite d'un blocage d'appariement (« aucun blocage » se
 * vérifie donc par l'absence de cette exception sur un tournoi complet).
 */
final class PairingDeadlockException extends TournamentDomainException
{
    public function __construct(int $unpairedTeams)
    {
        parent::__construct(
            "Blocage d'appariement : impossible d'apparier {$unpairedTeams} équipe(s) sans provoquer de revanche.",
        );
    }
}
