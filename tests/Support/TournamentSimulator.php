<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Tournament\Configuration\TournamentConfiguration;
use App\Domain\Tournament\Entity\Game;
use App\Domain\Tournament\TournamentEngine;
use RuntimeException;

/**
 * Aide de test : construit et déroule un concours complet de bout en bout.
 *
 * Résultats déterministes : l'équipe au seed le plus élevé gagne toujours, ce
 * qui produit une distribution de bilans prévisible (binomiale lorsque le
 * nombre de rondes ≤ log2(nombre d'équipes)).
 */
final class TournamentSimulator
{
    public static function build(TournamentConfiguration $config, int $teamCount, int $courtCount): TournamentEngine
    {
        $engine = new TournamentEngine($config);

        for ($i = 1; $i <= $teamCount; $i++) {
            $engine->registerTeam((string) $i, "Équipe {$i}", $i);
        }

        for ($c = 1; $c <= $courtCount; $c++) {
            $engine->addCourt("c{$c}", (string) $c);
        }

        return $engine;
    }

    /**
     * Déroule le concours jusqu'à la fin.
     *
     * @param  'forward'|'reverse'  $order  ordre de saisie des résultats (simule l'asynchronisme)
     * @return int nombre de parties jouées
     */
    public static function run(TournamentEngine $engine, string $order = 'forward'): int
    {
        $target = $engine->configuration()->pointsTarget;
        $engine->start();

        $played = 0;
        $guard = 0;

        while (! $engine->isCompleted()) {
            $playing = $engine->playingGames();

            if ($playing === []) {
                throw new RuntimeException(
                    'Blocage : aucune partie en cours alors que le concours n’est pas terminé.',
                );
            }

            if ($order === 'reverse') {
                $playing = array_reverse($playing);
            }

            foreach ($playing as $game) {
                self::recordDeterministicResult($engine, $game, $target);
                $played++;
            }

            if (++$guard > 1_000_000) {
                throw new RuntimeException('Simulation emballée : arrêt de sécurité.');
            }
        }

        return $played;
    }

    private static function recordDeterministicResult(TournamentEngine $engine, Game $game, int $target): void
    {
        $seedA = $engine->team($game->teamA)->seed;
        $seedB = $engine->team($game->teamB)->seed;
        $loserScore = max(0, $target - 6);

        if ($seedA > $seedB) {
            $engine->recordResult($game->id->value, $target, $loserScore);
        } else {
            $engine->recordResult($game->id->value, $loserScore, $target);
        }
    }
}
