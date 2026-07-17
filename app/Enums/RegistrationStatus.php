<?php

namespace App\Enums;

/**
 * Cycle de vie d'une demande d'inscription publique (distincte de l'équipe officielle).
 *
 *   pending  → soumise par les joueurs, en attente de validation
 *   confirmed → validée par l'organisateur
 *   checked_in → présence validée le jour du concours
 *   cancelled → annulée / refusée
 */
enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmée',
            self::CheckedIn => 'Présence validée',
            self::Cancelled => 'Annulée',
        };
    }

    /** Une inscription qui compte encore (ni annulée). */
    public function isActive(): bool
    {
        return $this !== self::Cancelled;
    }

    /** Peut être transformée en équipe officielle. */
    public function isCheckedIn(): bool
    {
        return $this === self::CheckedIn;
    }
}
