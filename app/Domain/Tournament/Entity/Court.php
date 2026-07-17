<?php

declare(strict_types=1);

namespace App\Domain\Tournament\Entity;

use App\Domain\Tournament\Enum\CourtState;
use App\Domain\Tournament\Exception\InvalidTournamentStateException;
use App\Domain\Tournament\ValueObject\CourtId;
use App\Domain\Tournament\ValueObject\GameId;

/**
 * Terrain de jeu : ressource rare attribuée dynamiquement aux parties couvertes.
 */
final class Court
{
    private CourtState $state = CourtState::Available;

    private ?GameId $game = null;

    public function __construct(
        public readonly CourtId $id,
        public readonly string $label,
    ) {}

    public function state(): CourtState
    {
        return $this->state;
    }

    public function isAvailable(): bool
    {
        return $this->state === CourtState::Available;
    }

    public function gameId(): ?GameId
    {
        return $this->game;
    }

    public function occupy(GameId $game): void
    {
        if ($this->state !== CourtState::Available) {
            throw InvalidTournamentStateException::because(
                "Le terrain {$this->label} est déjà occupé.",
            );
        }

        $this->state = CourtState::Occupied;
        $this->game = $game;
    }

    public function release(): void
    {
        $this->state = CourtState::Available;
        $this->game = null;
    }
}
