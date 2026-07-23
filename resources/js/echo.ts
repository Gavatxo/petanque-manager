import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Echo?: Echo<'reverb' | 'pusher'>;
        Pusher: typeof Pusher;
    }
}

// Le temps réel est optionnel et ne doit JAMAIS casser l'app : sans clé
// configurée, Pusher lève « You must pass your app key » au chargement, ce qui
// laisserait un écran blanc. On n'initialise donc Echo que dans le navigateur,
// si une clé existe, et sous try/catch. Les hooks (useTournamentEcho) vérifient
// déjà `window.Echo` et ne font rien s'il est absent.
//
// Deux modes, choisis selon les variables présentes au build :
//  - Pusher (hébergé)  → prod sur mutualisé (o2switch) : VITE_PUSHER_APP_KEY.
//  - Reverb (local)    → développement : VITE_REVERB_APP_KEY.
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (typeof window !== 'undefined' && (pusherKey || reverbKey)) {
    try {
        window.Pusher = Pusher;

        if (pusherKey) {
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: pusherKey,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'eu',
                wsHost: import.meta.env.VITE_PUSHER_HOST || undefined,
                forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });
        } else {
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: reverbKey,
                wsHost: import.meta.env.VITE_REVERB_HOST,
                wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
                wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });
        }
    } catch (error) {
        console.warn('Temps réel désactivé : configuration invalide.', error);
    }
}
