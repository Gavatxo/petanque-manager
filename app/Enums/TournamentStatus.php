<?php

namespace App\Enums;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case RegistrationOpen = 'registration_open';
    case CheckIn = 'checkin';
    case Running = 'running';
    case Finished = 'finished';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::RegistrationOpen => 'Inscriptions ouvertes',
            self::CheckIn => 'Validation des présents',
            self::Running => 'En cours',
            self::Finished => 'Terminé',
            self::Archived => 'Archivé',
        };
    }

    public function isArchived(): bool
    {
        return $this === self::Archived;
    }

    /**
     * Statuses an organizer may set manually from the edit screen in V1.
     * The later lifecycle (checkin, running, finished) is driven by the engine.
     *
     * @return array<int, self>
     */
    public static function organizerSelectable(): array
    {
        return [self::Draft, self::RegistrationOpen];
    }
}
