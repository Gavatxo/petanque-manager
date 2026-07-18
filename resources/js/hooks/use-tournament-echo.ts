import { router } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * S'abonne au canal temps réel d'un concours et recharge les données Inertia
 * de la page courante à chaque changement (résultat saisi, terrain libéré,
 * nouvelle partie, statut d'équipe…).
 */
export function useTournamentEcho(tournamentId: number): void {
    useEffect(() => {
        const echo = typeof window !== 'undefined' ? window.Echo : undefined;

        if (!echo) {
            return;
        }

        const channelName = `tournament.${tournamentId}`;

        echo.channel(channelName).listen('.updated', () => {
            router.reload();
        });

        return () => {
            echo.leave(channelName);
        };
    }, [tournamentId]);
}
