import { Head } from '@inertiajs/react';
import { CircleCheck, Clock, Crown } from 'lucide-react';

type Props = {
    tournamentName: string;
    registration: {
        team_name: string | null;
        status: string;
        status_label: string;
        players: { first_name: string; last_name: string; is_captain: boolean }[];
    };
};

export default function Registered({ tournamentName, registration }: Props) {
    return (
        <div className="bg-muted/40 flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <Head title="Demande d’inscription envoyée" />

            <div className="bg-card text-card-foreground w-full max-w-md space-y-6 rounded-xl border p-8 text-center shadow-sm">
                <div className="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <CircleCheck className="size-7" />
                </div>

                <div className="space-y-1">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Demande d’inscription envoyée&nbsp;!
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {registration.team_name ? (
                            <>
                                L’équipe{' '}
                                <strong className="text-foreground">
                                    {registration.team_name}
                                </strong>{' '}
                            </>
                        ) : (
                            'Votre équipe '
                        )}
                        a été enregistrée pour «&nbsp;{tournamentName}&nbsp;».
                    </p>
                </div>

                <div className="bg-amber-500/10 text-amber-700 dark:text-amber-400 flex items-center justify-center gap-2 rounded-md py-2 text-sm font-medium">
                    <Clock className="size-4" />
                    {registration.status_label} — en attente de validation par l’organisateur
                </div>

                <ul className="divide-border divide-y text-left text-sm">
                    {registration.players.map((player, index) => (
                        <li key={index} className="flex items-center gap-2 py-2">
                            {player.is_captain && (
                                <Crown className="size-4 shrink-0 text-amber-500" />
                            )}
                            <span className={player.is_captain ? 'font-medium' : ''}>
                                {player.first_name} {player.last_name}
                            </span>
                            {player.is_captain && (
                                <span className="text-muted-foreground ml-auto text-xs">
                                    Capitaine
                                </span>
                            )}
                        </li>
                    ))}
                </ul>

                <p className="text-muted-foreground text-xs">
                    Présentez-vous le jour du concours pour valider votre présence.
                </p>
            </div>
        </div>
    );
}
