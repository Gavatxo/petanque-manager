<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Knockout;

use App\Domain\Tournament\Enum\Division;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\ValueObject\TeamId;

/**
 * Moteur d'un tableau à élimination directe (phase finale), indépendant du moteur
 * Swiss de qualification.
 *
 * Gère :
 *  - les effectifs qui ne sont pas des puissances de 2, via un tour de cadrage
 *    et des exempts (les mieux classés passent le premier tour) ;
 *  - la propagation automatique des vainqueurs au tour suivant ;
 *  - le classement final (par tour d'élimination, puis seed).
 *
 * Placement : seeding standard « à l'américaine » — sans surprise, les meilleurs
 * seeds ne se rencontrent que le plus tard possible.
 */
final class KnockoutEngine
{
    private int $entrantCount;

    private int $bracketSize;

    private int $totalRounds;

    private int $cadrageGameCount = 0;

    /** @var array<string, KnockoutEntrant> indexé par identifiant d'équipe */
    private array $entrants = [];

    /** @var array<int, list<KnockoutGame>> tour => parties */
    private array $rounds = [];

    /** @var array<string, KnockoutGame> indexé par identifiant de partie */
    private array $gamesById = [];

    /** @var array<string, int> identifiant d'équipe => tour d'élimination */
    private array $eliminatedInRound = [];

    private ?TeamId $champion = null;

    /**
     * @param  list<KnockoutEntrant>  $entrants
     */
    public function __construct(array $entrants, private readonly ?Division $division = null)
    {
        if ($entrants === []) {
            throw InvalidTournamentStateException::because('Un tableau nécessite au moins une équipe.');
        }

        foreach ($entrants as $entrant) {
            if (isset($this->entrants[$entrant->id->value])) {
                throw InvalidTournamentStateException::because(
                    "Équipe en double dans le tableau : {$entrant->id->value}.",
                );
            }
            $this->entrants[$entrant->id->value] = $entrant;
        }

        $this->entrantCount = count($entrants);

        if ($this->entrantCount === 1) {
            // Tableau à une seule équipe : championne d'office.
            $this->bracketSize = 1;
            $this->totalRounds = 0;
            $this->champion = $entrants[0]->id;

            return;
        }

        $this->bracketSize = self::nextPowerOfTwo($this->entrantCount);
        $this->totalRounds = (int) log($this->bracketSize, 2);

        $this->buildBracket($entrants);
    }

    // ---------------------------------------------------------------------
    // Construction du tableau
    // ---------------------------------------------------------------------

    /**
     * @param  list<KnockoutEntrant>  $entrants
     */
    private function buildBracket(array $entrants): void
    {
        // Classe les équipes par seed (le meilleur en tête) : seed de tableau 1..N.
        $ranked = $entrants;
        usort(
            $ranked,
            static fn (KnockoutEntrant $a, KnockoutEntrant $b): int => $a->seed <=> $b->seed
                ?: strcmp($a->id->value, $b->id->value),
        );

        $order = self::seedOrder($this->bracketSize);

        // Premier tour.
        $round1 = [];
        for ($i = 0; $i < intdiv($this->bracketSize, 2); $i++) {
            $seedA = $order[2 * $i];
            $seedB = $order[2 * $i + 1];

            $teamA = $seedA <= $this->entrantCount ? $ranked[$seedA - 1]->id : null;
            $teamB = $seedB <= $this->entrantCount ? $ranked[$seedB - 1]->id : null;

            if ($teamA === null && $teamB === null) {
                throw InvalidTournamentStateException::because('Placement invalide : deux exempts appariés.');
            }

            $round1[] = $this->registerGame(new KnockoutGame("k1-{$i}", 1, $i, $teamA, $teamB));
        }
        $this->rounds[1] = $round1;

        // Tours suivants, vides pour l'instant.
        for ($round = 2; $round <= $this->totalRounds; $round++) {
            $games = [];
            $count = intdiv($this->bracketSize, 2 ** $round);
            for ($i = 0; $i < $count; $i++) {
                $games[] = $this->registerGame(new KnockoutGame("k{$round}-{$i}", $round, $i));
            }
            $this->rounds[$round] = $games;
        }

        // Résout les exempts du premier tour (cadrage) et propage.
        foreach ($round1 as $game) {
            if ($game->hasExactlyOneTeam()) {
                $game->winByWalkover();
                $this->afterFinish($game);
            } else {
                $this->cadrageGameCount++;
            }
        }

        // Sans exempt, le premier tour n'est pas un cadrage.
        if ($this->byeCount() === 0) {
            $this->cadrageGameCount = 0;
        }
    }

    private function registerGame(KnockoutGame $game): KnockoutGame
    {
        $this->gamesById[$game->id] = $game;

        return $game;
    }

    // ---------------------------------------------------------------------
    // Déroulé
    // ---------------------------------------------------------------------

    /**
     * Saisit le résultat d'une partie prête ; le vainqueur (score le plus élevé)
     * est propagé automatiquement au tour suivant.
     */
    public function recordResult(string $gameId, int $scoreA, int $scoreB): void
    {
        $game = $this->gamesById[$gameId]
            ?? throw InvalidTournamentStateException::because("Partie inconnue : {$gameId}.");

        $game->recordScore($scoreA, $scoreB);
        $this->afterFinish($game);
    }

    private function afterFinish(KnockoutGame $game): void
    {
        $loser = $game->loser();
        if ($loser !== null) {
            $this->eliminatedInRound[$loser->value] = $game->round;
        }

        $winner = $game->winner();
        if ($winner === null) {
            return;
        }

        if ($game->round >= $this->totalRounds) {
            $this->champion = $winner;

            return;
        }

        $parent = $this->rounds[$game->round + 1][intdiv($game->index, 2)];

        if ($game->index % 2 === 0) {
            $parent->fillSlotA($winner);
        } else {
            $parent->fillSlotB($winner);
        }
    }

    // ---------------------------------------------------------------------
    // Consultation
    // ---------------------------------------------------------------------

    public function division(): ?Division
    {
        return $this->division;
    }

    public function entrantCount(): int
    {
        return $this->entrantCount;
    }

    public function bracketSize(): int
    {
        return $this->bracketSize;
    }

    public function totalRounds(): int
    {
        return $this->totalRounds;
    }

    /** Nombre d'équipes exemptées du premier tour. */
    public function byeCount(): int
    {
        return $this->bracketSize - $this->entrantCount;
    }

    /** Nombre de parties de cadrage (premier tour, uniquement si effectif non puissance de 2). */
    public function cadrageGameCount(): int
    {
        return $this->cadrageGameCount;
    }

    public function hasCadrage(): bool
    {
        return $this->cadrageGameCount > 0;
    }

    public function isComplete(): bool
    {
        return $this->champion !== null;
    }

    public function champion(): ?TeamId
    {
        return $this->champion;
    }

    public function entrant(TeamId $id): KnockoutEntrant
    {
        return $this->entrants[$id->value]
            ?? throw InvalidTournamentStateException::because("Équipe inconnue : {$id->value}.");
    }

    /** @return list<KnockoutGame> */
    public function games(): array
    {
        return array_values($this->gamesById);
    }

    /**
     * @return list<KnockoutGame>
     */
    public function gamesInRound(int $round): array
    {
        return $this->rounds[$round] ?? [];
    }

    /** @return list<KnockoutGame> Parties prêtes à être jouées. */
    public function readyGames(): array
    {
        return array_values(array_filter(
            $this->gamesById,
            static fn (KnockoutGame $game): bool => $game->isReady(),
        ));
    }

    /**
     * Classement final : championne, finaliste, puis par tour d'élimination
     * décroissant (élimination plus tardive = mieux classée), à seed égal le
     * meilleur seed devant.
     *
     * @return list<RankedTeam>
     */
    public function finalRanking(): array
    {
        $entrants = array_values($this->entrants);

        usort($entrants, function (KnockoutEntrant $a, KnockoutEntrant $b): int {
            $elimA = $this->eliminatedInRound[$a->id->value] ?? PHP_INT_MAX;
            $elimB = $this->eliminatedInRound[$b->id->value] ?? PHP_INT_MAX;

            return $elimB <=> $elimA ?: $a->seed <=> $b->seed;
        });

        $ranking = [];
        foreach ($entrants as $position => $entrant) {
            $ranking[] = new RankedTeam(
                position: $position + 1,
                teamId: $entrant->id,
                name: $entrant->name,
                seed: $entrant->seed,
                isChampion: $this->champion !== null && $this->champion->equals($entrant->id),
                eliminatedInRound: $this->eliminatedInRound[$entrant->id->value] ?? null,
            );
        }

        return $ranking;
    }

    /**
     * Libellé d'un tour (Cadrage, 8es, Quarts, Demi-finales, Finale…).
     */
    public function roundLabel(int $round): string
    {
        if ($round < 1 || $round > $this->totalRounds) {
            throw InvalidTournamentStateException::because("Tour invalide : {$round}.");
        }

        if ($round === 1 && $this->byeCount() > 0) {
            return 'Cadrage';
        }

        $teamsInRound = intdiv($this->bracketSize, 2 ** ($round - 1));

        return match ($teamsInRound) {
            2 => 'Finale',
            4 => 'Demi-finales',
            8 => 'Quarts de finale',
            16 => '8es de finale',
            32 => '16es de finale',
            64 => '32es de finale',
            default => "Tour de {$teamsInRound}",
        };
    }

    // ---------------------------------------------------------------------
    // Placement (seeding standard)
    // ---------------------------------------------------------------------

    private static function nextPowerOfTwo(int $n): int
    {
        $power = 1;
        while ($power < $n) {
            $power *= 2;
        }

        return $power;
    }

    /**
     * Ordre de placement standard d'un tableau : les paires consécutives
     * (0,1), (2,3)… sont les affrontements du premier tour, avec seed 1 opposé au
     * dernier seed, etc.
     *
     * @return list<int>
     */
    private static function seedOrder(int $size): array
    {
        $order = [1, 2];

        for ($length = 2; $length < $size; $length *= 2) {
            $sum = $length * 2 + 1;
            $next = [];
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $sum - $seed;
            }
            $order = $next;
        }

        return $order;
    }
}
