import { Head } from '@inertiajs/react';
import { useTournamentEcho } from '@/hooks/use-tournament-echo';
import { BODY, C, DISPLAY, MONO } from '@/lib/petanque';

type Live = {
    key: string;
    label: string;
    opponent: string | null;
    court: string | null;
};

type Previous = {
    opponent: string | null;
    court: string | null;
    my_score: number | null;
    their_score: number | null;
    won: boolean;
};

type Props = {
    tournamentId: number;
    tournamentName: string;
    club: string | null;
    currentPhase: string | null;
    registrationStatusLabel: string;
    teamName: string;
    team: {
        name: string;
        wins: number;
        losses: number;
        in_progress: number;
        division: string | null;
        final_rank: number | null;
        round: { current: number; total: number };
        live: Live;
        previous: Previous | null;
        rank: { position: number; total: number; remaining: number };
    } | null;
};

function ordinal(n: number): string {
    return n === 1 ? '1re' : `${n}e`;
}

function TeamBox({ name, mine }: { name: string; mine: boolean }) {
    return (
        <div
            className="flex items-center justify-between rounded-lg px-3.5 py-3"
            style={{
                background: mine ? C.greenBg : C.cardAlt,
                border: `1.5px solid ${mine ? 'oklch(0.52 0.14 152 / 0.4)' : C.border}`,
            }}
        >
            <span
                className="text-[15px] font-bold"
                style={{ fontFamily: DISPLAY, color: mine ? C.greenText : C.ink }}
            >
                {name}
            </span>
            {mine && (
                <span
                    className="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                    style={{ background: C.green, color: 'white', letterSpacing: '0.06em' }}
                >
                    Vous
                </span>
            )}
        </div>
    );
}

function StatTile({ value, label, color }: { value: number; label: string; color: string }) {
    return (
        <div className="rounded-lg py-3 text-center" style={{ background: C.cardAlt, border: `1px solid ${C.borderSoft}` }}>
            <div className="text-[26px] leading-none font-bold tabular-nums" style={{ fontFamily: MONO, color }}>
                {value}
            </div>
            <div className="mt-1.5 text-[11px] font-semibold uppercase" style={{ color: C.muted, letterSpacing: '0.05em' }}>
                {label}
            </div>
        </div>
    );
}

export default function TeamStatus({
    tournamentId,
    tournamentName,
    club,
    currentPhase,
    registrationStatusLabel,
    teamName,
    team,
}: Props) {
    useTournamentEcho(tournamentId);

    const playing = team !== null && (team.live.key === 'playing' || team.live.key === 'covered');

    return (
        <div className="flex min-h-screen flex-col items-center px-4 py-8" style={{ background: C.bg, fontFamily: BODY }}>
            <Head title={`Suivi — ${teamName}`} />

            <div className="w-full max-w-md space-y-4">
                <header className="text-center">
                    <div
                        className="mx-auto mb-3 flex items-center justify-center rounded-full"
                        style={{ background: C.greenBg, width: 52, height: 52 }}
                    >
                        <span className="text-2xl" role="img" aria-label="boule">
                            🎯
                        </span>
                    </div>
                    <h1 className="text-[26px] font-extrabold" style={{ fontFamily: DISPLAY, color: C.ink }}>
                        {teamName}
                    </h1>
                    <p className="mt-1 text-[13px]" style={{ color: C.muted }}>
                        {tournamentName}
                        {club ? ` · ${club}` : ''}
                    </p>
                </header>

                {team === null ? (
                    <div
                        className="rounded-2xl p-6 text-center"
                        style={{ background: C.card, border: `1px solid ${C.border}` }}
                    >
                        <span
                            className="inline-block rounded-full px-3 py-1 text-xs font-semibold"
                            style={{ background: C.amberBg, color: C.amberText }}
                        >
                            {registrationStatusLabel}
                        </span>
                        <p className="mt-3 text-sm" style={{ color: C.muted }}>
                            Votre équipe n’est pas encore engagée dans le concours. Cette page se met à jour
                            automatiquement.
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Match en cours */}
                        <section
                            className="rounded-2xl p-5"
                            style={{
                                background: C.card,
                                border: `1.5px solid ${playing ? 'oklch(0.52 0.14 152 / 0.5)' : C.border}`,
                                boxShadow: playing ? '0 0 0 3px oklch(0.52 0.14 152 / 0.08)' : 'none',
                            }}
                        >
                            <div className="mb-3.5 flex items-center justify-between">
                                <span
                                    className="flex items-center gap-2 text-[13px] font-bold uppercase"
                                    style={{ fontFamily: DISPLAY, color: playing ? C.greenText : C.muted, letterSpacing: '0.06em' }}
                                >
                                    {playing && (
                                        <span className="relative flex size-2.5">
                                            <span className="absolute inline-flex size-full animate-ping rounded-full opacity-60" style={{ background: C.green }} />
                                            <span className="relative inline-flex size-2.5 rounded-full" style={{ background: C.green }} />
                                        </span>
                                    )}
                                    {team.live.label}
                                </span>
                                {team.live.court && (
                                    <span
                                        className="rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                        style={{ fontFamily: DISPLAY, background: C.primarySoft, color: C.primarySoftText, letterSpacing: '0.04em' }}
                                    >
                                        TERRAIN {team.live.court}
                                    </span>
                                )}
                            </div>

                            {team.live.opponent ? (
                                <div className="space-y-2">
                                    <TeamBox name={team.name} mine />
                                    <div className="text-center text-[11px] font-bold" style={{ fontFamily: DISPLAY, color: C.muted, letterSpacing: '0.14em' }}>
                                        VS
                                    </div>
                                    <TeamBox name={team.live.opponent} mine={false} />
                                </div>
                            ) : (
                                <p className="py-2 text-center text-sm" style={{ color: C.muted }}>
                                    {team.live.key === 'done'
                                        ? 'Concours terminé pour votre équipe.'
                                        : 'Vous serez bientôt fixés sur votre prochain adversaire.'}
                                </p>
                            )}
                        </section>

                        {/* Parcours */}
                        <section
                            className="rounded-2xl p-5"
                            style={{ background: C.card, border: `1px solid ${C.border}` }}
                        >
                            <div className="mb-3.5 flex items-baseline justify-between">
                                <h2 className="text-[15px] font-bold" style={{ fontFamily: DISPLAY, color: C.ink }}>
                                    Votre parcours
                                </h2>
                                <span className="text-xs font-semibold" style={{ color: C.muted }}>
                                    Ronde {team.round.current}/{team.round.total}
                                </span>
                            </div>
                            <div className="grid grid-cols-3 gap-2.5">
                                <StatTile value={team.wins} label="Victoires" color={C.green} />
                                <StatTile value={team.losses} label="Défaites" color={C.accent} />
                                <StatTile value={team.in_progress} label="En cours" color={C.primary} />
                            </div>

                            {team.previous && (
                                <div className="mt-3.5 rounded-lg px-3.5 py-3" style={{ background: C.cardAlt, border: `1px solid ${C.borderSoft}` }}>
                                    <div className="mb-1 text-[11px] font-bold uppercase" style={{ color: C.muted, letterSpacing: '0.05em' }}>
                                        Partie précédente
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm" style={{ color: C.ink2 }}>
                                            vs {team.previous.opponent ?? 'Adversaire'}
                                            {team.previous.court ? ` · Terrain ${team.previous.court}` : ''}
                                        </span>
                                        <span className="flex items-center gap-2">
                                            <span className="text-[15px] font-bold tabular-nums" style={{ fontFamily: MONO, color: C.ink }}>
                                                {team.previous.my_score ?? '—'} — {team.previous.their_score ?? '—'}
                                            </span>
                                            <span
                                                className="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                                style={{
                                                    background: team.previous.won ? C.greenBg : C.neutralBg,
                                                    color: team.previous.won ? C.greenText : C.neutralText,
                                                    letterSpacing: '0.05em',
                                                }}
                                            >
                                                {team.previous.won ? 'Gagné' : 'Perdu'}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            )}
                        </section>

                        {/* Position provisoire */}
                        {team.final_rank === null && currentPhase !== null && (
                            <section
                                className="rounded-2xl px-5 py-4"
                                style={{ background: C.amberBg, border: `1px solid oklch(0.70 0.18 80 / 0.35)` }}
                            >
                                <p className="text-sm" style={{ color: C.amberText }}>
                                    <span className="font-bold">{ordinal(team.rank.position)} position</span> provisoire
                                    sur {team.rank.total} équipes
                                    {team.rank.remaining > 0
                                        ? ` — encore ${team.rank.remaining} partie(s) à jouer.`
                                        : ' — qualifications terminées.'}
                                </p>
                            </section>
                        )}
                    </>
                )}

                <p className="pt-1 text-center text-xs" style={{ color: C.muted }}>
                    {currentPhase === null
                        ? 'Le concours n’a pas encore démarré.'
                        : 'La page se met à jour automatiquement.'}
                </p>
            </div>
        </div>
    );
}
