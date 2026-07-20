<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Configuration;

/**
 * Suggère un format de concours (parties qualificatives, tableaux, points) à
 * partir du nombre d'équipes réellement inscrites.
 *
 * Le nombre de tableaux croît avec la taille du concours (principal, puis
 * complémentaire, consolante…). Le nombre de rondes de brassage reste toujours
 * suffisant pour que chaque tableau soit atteignable, la répartition se faisant
 * par nombre de victoires (index tableau = rondes − victoires).
 *
 * Ces valeurs ne sont qu'une proposition : l'organisateur peut les ajuster au
 * moment du tirage.
 */
final readonly class FormatSuggestion
{
    public function __construct(
        public int $qualifyingRounds,
        public int $tableauxCount,
        public int $pointsTarget,
    ) {}

    public static function forTeamCount(int $teamCount): self
    {
        $tableaux = match (true) {
            $teamCount >= 32 => 4,
            $teamCount >= 16 => 3,
            $teamCount >= 8 => 2,
            default => 1,
        };

        $rounds = match (true) {
            $teamCount >= 64 => 6,
            $teamCount >= 32 => 5,
            $teamCount >= 16 => 4,
            default => 3,
        };

        // Garantit assez de rondes pour peupler chaque tableau : une équipe qui
        // perd tout doit pouvoir rejoindre le dernier tableau (index = rondes −
        // victoires, plafonné à tableaux − 1).
        $rounds = max($rounds, $tableaux - 1);

        return new self($rounds, $tableaux, 13);
    }

    /**
     * @return array{qualifying_rounds: int, tableaux_count: int, points_target: int}
     */
    public function toArray(): array
    {
        return [
            'qualifying_rounds' => $this->qualifyingRounds,
            'tableaux_count' => $this->tableauxCount,
            'points_target' => $this->pointsTarget,
        ];
    }
}
