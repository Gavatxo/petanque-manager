import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Echo?: Echo<'reverb'>;
        Pusher: typeof Pusher;
    }
}

// Le temps réel (Reverb) est optionnel : sans clé configurée (VITE_REVERB_APP_KEY),
// Pusher lève « You must pass your app key » au chargement. Cette exception, non
// catchée au niveau module, empêchait app.tsx de s'exécuter et laissait un écran
// blanc. On n'initialise donc Echo que dans le navigateur ET si la clé existe, le
// tout sous try/catch : une mauvaise config temps réel ne doit jamais casser l'app.
// Les hooks (useTournamentEcho) vérifient déjà `window.Echo` et no-op si absent.
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (typeof window !== 'undefined' && reverbKey) {
    try {
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
            wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } catch (error) {
        console.warn('Temps réel désactivé : configuration Reverb invalide.', error);
    }
}
