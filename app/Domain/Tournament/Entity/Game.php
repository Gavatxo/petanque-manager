<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Entity;

use App\Domain\Tournament\Enum\GameState;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\ValueObject\CourtId;
use App\Domain\Tournament\ValueObject\GameId;
use App\Domain\Tournament\ValueObject\TeamId;

/**
 * Une partie (rencontre) entre deux équipes, sur un tour de qualification donné.
 */
final class Game
{
    private GameState $state = GameState::Pending;

    private ?CourtId $court = null;

    private ?int $scoreA = null;

    private ?int $scoreB = null;

    private ?TeamId $winner = null;

    public function __construct(
        public readonly GameId $id,
        public readonly int $round,
        public readonly TeamId $teamA,
        public readonly TeamId $teamB,
    ) {}

    public function state(): GameState
    {
        return $this->state;
    }

    public function isPending(): bool
    {
        return $this->state === GameState::Pending;
    }

    public function isPlaying(): bool
    {
        return $this->state === GameState::Playing;
    }

    public function isFinished(): bool
    {
        return $this->state === GameState::Finished;
    }

    public function courtId(): ?CourtId
    {
        return $this->court;
    }

    public function scoreA(): ?int
    {
        return $this->scoreA;
    }

    public function scoreB(): ?int
    {
        return $this->scoreB;
    }

    public function winner(): ?TeamId
    {
        return $this->winner;
    }

    public function loser(): ?TeamId
    {
        if ($this->winner === null) {
            return null;
        }

        return $this->winner->equals($this->teamA) ? $this->teamB : $this->teamA;
    }

    public function involves(TeamId $team): bool
    {
        return $this->teamA->equals($team) || $this->teamB->equals($team);
    }

    public function assignCourt(CourtId $court): void
    {
        if ($this->state !== GameState::Pending) {
            throw InvalidTournamentStateException::because(
                "La partie {$this->id->value} n'est pas en attente de terrain.",
            );
        }

        $this->court = $court;
        $this->state = GameState::Playing;
    }

    public function recordScore(int $scoreA, int $scoreB): void
    {
        if ($this->state !== GameState::Playing) {
            throw InvalidTournamentStateException::because(
                "La partie {$this->id->value} n'est pas en cours.",
            );
        }

        $this->scoreA = $scoreA;
        $this->scoreB = $scoreB;
        $this->winner = $scoreA > $scoreB ? $this->teamA : $this->teamB;
        $this->state = GameState::Finished;
    }
}
