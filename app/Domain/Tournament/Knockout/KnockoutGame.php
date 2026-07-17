<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Knockout;

use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\Knockout\Enum\KnockoutGameState;
use App\Domain\Tournament\ValueObject\TeamId;

/**
 * Une partie d'un tableau à élimination directe.
 *
 * Un emplacement (slot) vide vaut :
 *  - au premier tour : un exempt (bye) — l'équipe présente passe le tour ;
 *  - aux tours suivants : un adversaire encore à déterminer (vainqueur à venir).
 */
final class KnockoutGame
{
    private KnockoutGameState $state;

    private ?TeamId $winner = null;

    private ?int $scoreA = null;

    private ?int $scoreB = null;

    private bool $walkover = false;

    public function __construct(
        public readonly string $id,
        public readonly int $round,
        public readonly int $index,
        private ?TeamId $slotA = null,
        private ?TeamId $slotB = null,
    ) {
        $this->state = $this->hasBothTeams()
            ? KnockoutGameState::Ready
            : KnockoutGameState::Awaiting;
    }

    public function slotA(): ?TeamId
    {
        return $this->slotA;
    }

    public function slotB(): ?TeamId
    {
        return $this->slotB;
    }

    public function state(): KnockoutGameState
    {
        return $this->state;
    }

    public function winner(): ?TeamId
    {
        return $this->winner;
    }

    public function scoreA(): ?int
    {
        return $this->scoreA;
    }

    public function scoreB(): ?int
    {
        return $this->scoreB;
    }

    public function isReady(): bool
    {
        return $this->state === KnockoutGameState::Ready;
    }

    public function isFinished(): bool
    {
        return $this->state === KnockoutGameState::Finished;
    }

    public function isWalkover(): bool
    {
        return $this->walkover;
    }

    public function hasBothTeams(): bool
    {
        return $this->slotA !== null && $this->slotB !== null;
    }

    public function hasExactlyOneTeam(): bool
    {
        return ($this->slotA !== null) !== ($this->slotB !== null);
    }

    public function loser(): ?TeamId
    {
        if ($this->winner === null || $this->walkover) {
            return null;
        }

        return $this->winner->equals($this->slotA) ? $this->slotB : $this->slotA;
    }

    public function fillSlotA(TeamId $team): void
    {
        $this->slotA = $team;
        $this->refreshReadiness();
    }

    public function fillSlotB(TeamId $team): void
    {
        $this->slotB = $team;
        $this->refreshReadiness();
    }

    /** Exempt : l'unique équipe présente passe le tour sans jouer. */
    public function winByWalkover(): void
    {
        if (! $this->hasExactlyOneTeam()) {
            throw InvalidTournamentStateException::because(
                "La partie {$this->id} n'est pas un exempt.",
            );
        }

        $this->winner = $this->slotA ?? $this->slotB;
        $this->walkover = true;
        $this->state = KnockoutGameState::Finished;
    }

    public function recordScore(int $scoreA, int $scoreB): void
    {
        if ($this->state !== KnockoutGameState::Ready) {
            throw InvalidTournamentStateException::because(
                "La partie {$this->id} n'est pas prête à être jouée.",
            );
        }

        if ($scoreA === $scoreB) {
            throw InvalidTournamentStateException::because('Une partie ne peut se terminer sur une égalité.');
        }

        if ($scoreA < 0 || $scoreB < 0) {
            throw InvalidTournamentStateException::because('Les scores ne peuvent pas être négatifs.');
        }

        $this->scoreA = $scoreA;
        $this->scoreB = $scoreB;
        $this->winner = $scoreA > $scoreB ? $this->slotA : $this->slotB;
        $this->state = KnockoutGameState::Finished;
    }

    private function refreshReadiness(): void
    {
        if ($this->state === KnockoutGameState::Awaiting && $this->hasBothTeams()) {
            $this->state = KnockoutGameState::Ready;
        }
    }
}
