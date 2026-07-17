import type { TournamentStatus } from '@/types';

type BadgeVariant = 'default' | 'secondary' | 'outline' | 'destructive';

export function statusBadgeVariant(status: TournamentStatus): BadgeVariant {
    switch (status) {
        case 'registration_open':
        case 'running':
            return 'default';
        case 'archived':
            return 'outline';
        default:
            return 'secondary';
    }
}

export function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(new Date(iso));
}

export function formatDateTime(iso: string | null): string {
    if (!iso) {
        return 'Date à définir';
    }

    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(iso));
}

/** Convert an ISO date into the value a `datetime-local` input expects. */
export function toDateTimeLocalValue(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);
    const pad = (value: number) => String(value).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
