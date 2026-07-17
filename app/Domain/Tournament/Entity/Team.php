<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Entity;

use App\Domain\Tournament\Enum\Division;
use App\Domain\Tournament\Enum\TeamState;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\ValueObject\GameId;
use App\Domain\Tournament\ValueObject\TeamId;

/**
 * Équipe engagée dans un concours.
 *
 * Porte son bilan (victoires/défaites), son historique d'adversaires
 * (pour interdire les revanches) et son état dans le déroulé.
 */
final class Team
{
    private int $wins = 0;

    private int $losses = 0;

    private int $gamesPlayed = 0;

    private int $byes = 0;

    /**
     * Adversaires déjà rencontrés, indexés par identifiant pour une recherche O(1).
     *
     * @var array<string, true>
     */
    private array $opponents = [];

    private TeamState $state = TeamState::Idle;

    private ?GameId $currentGame = null;

    private ?Division $division = null;

    public function __construct(
        public readonly TeamId $id,
        public readonly string $name,
        public readonly int $seed,
    ) {}

    public function wins(): int
    {
        return $this->wins;
    }

    public function losses(): int
    {
        return $this->losses;
    }

    public function gamesPlayed(): int
    {
        return $this->gamesPlayed;
    }

    public function byes(): int
    {
        return $this->byes;
    }

    public function state(): TeamState
    {
        return $this->state;
    }

    public function division(): ?Division
    {
        return $this->division;
    }

    public function currentGameId(): ?GameId
    {
        return $this->currentGame;
    }

    public function hasPlayed(TeamId $opponent): bool
    {
        return isset($this->opponents[$opponent->value]);
    }

    /**
     * @return list<string>
     */
    public function opponentHistory(): array
    {
        // Les clés numériques sont converties en int par PHP : on rétablit le type identifiant.
        return array_map(strval(...), array_keys($this->opponents));
    }

    public function isAvailable(): bool
    {
        return $this->state === TeamState::Available;
    }

    public function isWaiting(): bool
    {
        return $this->state === TeamState::Waiting;
    }

    public function isCovered(): bool
    {
        return $this->state === TeamState::Covered;
    }

    public function isPlaying(): bool
    {
        return $this->state === TeamState::Playing;
    }

    public function isQualified(): bool
    {
        return $this->state === TeamState::Qualified;
    }

    /** Rend l'équipe disponible pour le prochain appariement. */
    public function markAvailable(): void
    {
        $this->state = TeamState::Available;
        $this->currentGame = null;
    }

    /** Aucun adversaire disponible ce tour : l'équipe patiente. */
    public function markWaiting(): void
    {
        $this->state = TeamState::Waiting;
        $this->currentGame = null;
    }

    /** Un adversaire lui est attribué : elle est couverte. */
    public function assignToGame(GameId $game): void
    {
        $this->state = TeamState::Covered;
        $this->currentGame = $game;
    }

    /** Un terrain est attribué à sa partie : elle joue. */
    public function startPlaying(): void
    {
        if ($this->state !== TeamState::Covered) {
            throw InvalidTournamentStateException::because(
                "L'équipe {$this->id->value} doit être couverte avant de jouer.",
            );
        }

        $this->state = TeamState::Playing;
    }

    /**
     * Enregistre le résultat d'une partie et met à jour l'historique.
     */
    public function applyResult(bool $won, TeamId $opponent): void
    {
        $this->opponents[$opponent->value] = true;
        $this->gamesPlayed++;

        if ($won) {
            $this->wins++;
        } else {
            $this->losses++;
        }

        $this->state = TeamState::Available;
        $this->currentGame = null;
    }

    /**
     * Exempt : l'équipe passe le tour, comptabilisé comme une victoire,
     * sans adversaire ajouté à l'historique.
     */
    public function awardBye(): void
    {
        $this->wins++;
        $this->byes++;
        $this->gamesPlayed++;
        $this->state = TeamState::Waiting;
        $this->currentGame = null;
    }

    public function qualify(Division $division): void
    {
        $this->division = $division;
        $this->state = TeamState::Qualified;
        $this->currentGame = null;
    }
}
