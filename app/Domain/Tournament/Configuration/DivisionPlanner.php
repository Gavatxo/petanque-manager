<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Configuration;

/**
 * Répartit les équipes qualifiées dans les tableaux (A/B/C/D) par classement
 * (victoires, puis goal average), avec des tailles choisies par l'organisateur.
 *
 * Les tableaux du haut ont une taille libre (idéalement une puissance de 2 pour
 * éviter les qualifiés d'office) ; le dernier tableau absorbe le reste.
 */
final class DivisionPlanner
{
    private const LABELS = ['A', 'B', 'C', 'D'];

    /**
     * Tailles suggérées des tableaux du HAUT (longueur = tableauxCount − 1), en
     * puissances de 2, réparties de façon équilibrée et décroissante. Le dernier
     * tableau prend le reste (non renvoyé ici).
     *
     * @return list<int>
     */
    public static function suggestUpperSizes(int $teamCount, int $tableauxCount): array
    {
        $k = max(1, min(4, $tableauxCount));
        $upper = [];
        $remaining = max(0, $teamCount);

        for ($i = 0; $i < $k - 1; $i++) {
            $tableauxLeft = $k - $i;
            // Laisser au moins une équipe à chaque tableau restant.
            $maxSize = max(1, $remaining - ($tableauxLeft - 1));
            // Cible ≥ moyenne pour un classement décroissant (A le plus grand).
            $target = (int) ceil($remaining / $tableauxLeft);
            $size = min(self::nearestPowerOfTwo($target), self::largestPowerOfTwoAtMost($maxSize));
            $size = max(1, $size);

            $upper[] = $size;
            $remaining -= $size;
        }

        return $upper;
    }

    /**
     * Toutes les tailles (tableaux du haut + reste) pour un aperçu.
     *
     * @param  list<int>  $upperSizes
     * @return list<int>
     */
    public static function fullSizes(int $teamCount, int $tableauxCount, array $upperSizes): array
    {
        $k = max(1, min(4, $tableauxCount));
        $sizes = [];
        $remaining = $teamCount;

        for ($i = 0; $i < $k; $i++) {
            $isLast = $i === $k - 1;
            $size = $isLast ? $remaining : max(0, min($upperSizes[$i] ?? 0, $remaining));
            $sizes[] = $size;
            $remaining -= $size;
        }

        return $sizes;
    }

    /**
     * Affecte chaque équipe (identifiants classés, meilleure en premier) à un
     * tableau et un seed d'entrée. Le dernier tableau prend le reste.
     *
     * @param  list<int>  $rankedTeamIds  identifiants classés (meilleur d'abord)
     * @param  list<int>  $upperSizes  tailles des tableaux du haut
     * @return array<int, array{division: string, seed: int}> teamId => tableau + seed
     */
    public static function plan(array $rankedTeamIds, array $upperSizes, int $tableauxCount): array
    {
        $labels = array_slice(self::LABELS, 0, max(1, min(4, $tableauxCount)));
        $total = count($rankedTeamIds);
        $assignment = [];
        $cursor = 0;

        foreach ($labels as $i => $label) {
            $isLast = $i === count($labels) - 1;
            $size = $isLast ? $total - $cursor : max(0, min($upperSizes[$i] ?? 0, $total - $cursor));

            for ($s = 0; $s < $size; $s++) {
                $assignment[$rankedTeamIds[$cursor]] = ['division' => $label, 'seed' => $s + 1];
                $cursor++;
            }
        }

        return $assignment;
    }

    public static function isPowerOfTwo(int $n): bool
    {
        return $n >= 1 && ($n & ($n - 1)) === 0;
    }

    private static function nearestPowerOfTwo(int $n): int
    {
        if ($n < 1) {
            return 1;
        }

        $lower = self::largestPowerOfTwoAtMost($n);
        $upper = $lower * 2;

        // Égalité de distance : on prend la puissance supérieure (tableaux plus pleins).
        return ($n - $lower) < ($upper - $n) ? $lower : $upper;
    }

    private static function largestPowerOfTwoAtMost(int $n): int
    {
        $power = 1;
        while ($power * 2 <= $n) {
            $power *= 2;
        }

        return $power;
    }
}
