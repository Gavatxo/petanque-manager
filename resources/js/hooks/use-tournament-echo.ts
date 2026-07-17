import { router } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * S'abonne au canal temps réel d'un concours et recharge les données Inertia
 * de la page courante à chaque changement (résultat saisi, terrain libéré,
 * nouvelle partie, statut d'équipe…).
 */
export function useTournamentEcho(tournamentId: number): void {
    useEffect(() => {
        if (typeof window === 'undefined' || !window.Echo) {
            return;
        }

        const channelName = `tournament.${tournamentId}`;

        window.Echo.channel(channelName).listen('.updated', () => {
            router.reload();
        });

        return () => {
            window.Echo.leave(channelName);
        };
    }, [tournamentId]);
}
