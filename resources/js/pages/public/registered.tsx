import { Head } from '@inertiajs/react';
import { CircleCheck, Crown } from 'lucide-react';

type Props = {
    tournamentName: string;
    team: {
        name: string;
        players: { first_name: string; last_name: string; is_captain: boolean }[];
    };
};

export default function Registered({ tournamentName, team }: Props) {
    return (
        <div className="bg-muted/40 flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <Head title="Inscription confirmée" />

            <div className="bg-card text-card-foreground w-full max-w-md space-y-6 rounded-xl border p-8 text-center shadow-sm">
                <div className="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <CircleCheck className="size-7" />
                </div>

                <div className="space-y-1">
                    <h1 className="text-xl font-semibold tracking-tight">Inscription confirmée&nbsp;!</h1>
                    <p className="text-muted-foreground text-sm">
                        L’équipe <strong className="text-foreground">{team.name}</strong> est inscrite
                        au concours «&nbsp;{tournamentName}&nbsp;».
                    </p>
                </div>

                <ul className="divide-border divide-y text-left text-sm">
                    {team.players.map((player, index) => (
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
