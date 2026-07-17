<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Pairing;

use App\Domain\Tournament\Entity\Team;
use App\Domain\Tournament\Exception\PairingDeadlockException;

/**
 * Moteur d'appariement d'une passe (une ronde) de concours libre.
 *
 * Garanties :
 *  - deux équipes s'étant déjà rencontrées ne sont jamais réappariées (pas de revanche) ;
 *  - les équipes de même bilan (victoires) sont appariées en priorité ;
 *  - si un appariement complet sans revanche est possible, il est trouvé
 *    (recherche exhaustive avec retour arrière) ; sinon une
 *    {@see PairingDeadlockException} est levée.
 *
 * Nombre impair d'équipes : la plus « faible » (moins d'exempts, puis moins de
 * victoires) est exemptée.
 */
final class Matchmaker
{
    /**
     * @param  list<Team>  $teams  équipes disponibles, toutes au même nombre de parties jouées
     */
    public function pair(array $teams): PairingResult
    {
        $byeTeam = null;

        if (count($teams) % 2 === 1) {
            $byeTeam = $this->selectByeTeam($teams);
            $teams = array_values(array_filter(
                $teams,
                static fn (Team $team): bool => ! $team->id->equals($byeTeam->id),
            ));
        }

        // Bilan décroissant puis seed : ordre déterministe et regroupement par victoires.
        usort(
            $teams,
            static fn (Team $a, Team $b): int => $b->wins() <=> $a->wins() ?: $a->seed <=> $b->seed,
        );

        $pairs = $this->findMatching($teams);

        if ($pairs === null) {
            throw new PairingDeadlockException(count($teams));
        }

        return new PairingResult($pairs, $byeTeam?->id);
    }

    /**
     * Recherche un appariement parfait sans revanche par retour arrière.
     *
     * Heuristique « équipe la plus contrainte d'abord » (celle qui a le moins
     * d'adversaires possibles) pour élaguer l'arbre de recherche : sur des
     * graphes de Swiss (exclusions rares), la première tentative aboutit
     * quasiment toujours sans retour arrière.
     *
     * @param  list<Team>  $teams
     * @return list<PairingPair>|null null si aucun appariement complet n'existe
     */
    private function findMatching(array $teams): ?array
    {
        if ($teams === []) {
            return [];
        }

        $anchorIndex = $this->mostConstrainedIndex($teams);
        $anchor = $teams[$anchorIndex];
        unset($teams[$anchorIndex]);
        $teams = array_values($teams);

        foreach ($this->candidatesFor($anchor, $teams) as $candidate) {
            $rest = array_values(array_filter(
                $teams,
                static fn (Team $team): bool => ! $team->id->equals($candidate->id),
            ));

            $sub = $this->findMatching($rest);

            if ($sub !== null) {
                array_unshift($sub, new PairingPair($anchor->id, $candidate->id));

                return $sub;
            }
        }

        return null;
    }

    /**
     * Adversaires possibles pour une équipe, jamais rencontrés, triés par
     * proximité de bilan (revanches exclues d'office).
     *
     * @param  list<Team>  $teams
     * @return list<Team>
     */
    private function candidatesFor(Team $anchor, array $teams): array
    {
        $candidates = array_values(array_filter(
            $teams,
            static fn (Team $team): bool => ! $anchor->hasPlayed($team->id),
        ));

        usort(
            $candidates,
            static fn (Team $a, Team $b): int => abs($anchor->wins() - $a->wins()) <=> abs($anchor->wins() - $b->wins())
                ?: $a->seed <=> $b->seed,
        );

        return $candidates;
    }

    /**
     * @param  list<Team>  $teams
     */
    private function mostConstrainedIndex(array $teams): int
    {
        $bestIndex = 0;
        $fewestOptions = PHP_INT_MAX;

        foreach ($teams as $index => $team) {
            $options = 0;

            foreach ($teams as $otherIndex => $other) {
                if ($index !== $otherIndex && ! $team->hasPlayed($other->id)) {
                    $options++;
                }
            }

            if ($options < $fewestOptions) {
                $fewestOptions = $options;
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  list<Team>  $teams
     */
    private function selectByeTeam(array $teams): Team
    {
        $ranked = $teams;

        usort(
            $ranked,
            static fn (Team $a, Team $b): int => $a->byes() <=> $b->byes()
                ?: $a->wins() <=> $b->wins()
                ?: $b->seed <=> $a->seed,
        );

        return $ranked[0];
    }
}
