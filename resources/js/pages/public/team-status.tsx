import { Head } from '@inertiajs/react';
import { MapPin, Radar, Swords, Trophy } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useTournamentEcho } from '@/hooks/use-tournament-echo';

type Live = {
    key: string;
    label: string;
    opponent: string | null;
    court: string | null;
};

type Props = {
    tournamentId: number;
    tournamentName: string;
    currentPhase: string | null;
    registrationStatusLabel: string;
    teamName: string;
    team: {
        name: string;
        wins: number;
        losses: number;
        division: string | null;
        final_rank: number | null;
        live: Live;
    } | null;
};

const STATE_STYLES: Record<string, string> = {
    playing: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
    covered: 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/30',
    waiting: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
    done: 'bg-muted text-foreground border-border',
};

export default function TeamStatus({
    tournamentId,
    tournamentName,
    currentPhase,
    registrationStatusLabel,
    teamName,
    team,
}: Props) {
    useTournamentEcho(tournamentId);

    return (
        <div className="bg-muted/40 flex min-h-screen flex-col items-center px-4 py-10">
            <Head title={`Suivi — ${teamName}`} />

            <div className="w-full max-w-md space-y-5">
                <header className="text-center">
                    <div className="text-muted-foreground flex items-center justify-center gap-1.5 text-xs">
                        <Radar className="size-3.5" />
                        Suivi en direct
                    </div>
                    <h1 className="mt-1 text-2xl font-semibold tracking-tight">{teamName}</h1>
                    <p className="text-muted-foreground text-sm">{tournamentName}</p>
                </header>

                {team === null ? (
                    <div className="bg-card text-card-foreground rounded-xl border p-6 text-center shadow-sm">
                        <Badge variant="secondary">{registrationStatusLabel}</Badge>
                        <p className="text-muted-foreground mt-3 text-sm">
                            Votre équipe n’est pas encore engagée dans le concours. Cette page se
                            mettra à jour automatiquement.
                        </p>
                    </div>
                ) : (
                    <>
                        <div
                            className={`rounded-xl border p-6 text-center shadow-sm ${STATE_STYLES[team.live.key] ?? STATE_STYLES.waiting}`}
                        >
                            {team.live.key === 'done' ? (
                                <Trophy className="mx-auto mb-2 size-8" />
                            ) : (
                                <div className="mb-2 flex justify-center">
                                    <span className="relative flex size-3">
                                        <span className="absolute inline-flex size-full animate-ping rounded-full bg-current opacity-60" />
                                        <span className="relative inline-flex size-3 rounded-full bg-current" />
                                    </span>
                                </div>
                            )}
                            <p className="text-lg font-semibold">{team.live.label}</p>
                            {team.live.opponent && (
                                <p className="mt-2 flex items-center justify-center gap-1.5 text-sm">
                                    <Swords className="size-4" />
                                    contre {team.live.opponent}
                                </p>
                            )}
                            {team.live.court && (
                                <p className="mt-1 flex items-center justify-center gap-1.5 text-sm font-medium">
                                    <MapPin className="size-4" />
                                    Terrain {team.live.court}
                                </p>
                            )}
                        </div>

                        <div className="bg-card text-card-foreground grid grid-cols-3 gap-2 rounded-xl border p-4 text-center shadow-sm">
                            <div>
                                <p className="text-2xl font-semibold tabular-nums">{team.wins}</p>
                                <p className="text-muted-foreground text-xs">Victoires</p>
                            </div>
                            <div>
                                <p className="text-2xl font-semibold tabular-nums">{team.losses}</p>
                                <p className="text-muted-foreground text-xs">Défaites</p>
                            </div>
                            <div>
                                <p className="text-2xl font-semibold">{team.division ?? '—'}</p>
                                <p className="text-muted-foreground text-xs">Tableau</p>
                            </div>
                        </div>
                    </>
                )}

                <p className="text-muted-foreground text-center text-xs">
                    {currentPhase === null
                        ? 'Le concours n’a pas encore démarré.'
                        : 'Mise à jour automatique à chaque résultat.'}
                </p>
            </div>
        </div>
    );
}
