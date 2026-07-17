import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTournamentEcho } from '@/hooks/use-tournament-echo';
import { DISPLAY, MONO } from '@/lib/petanque';

type CourtVM = {
    label: string;
    status: 'playing' | 'finished' | 'free';
    team_a: string | null;
    team_b: string | null;
    score_a: number | null;
    score_b: number | null;
    winner_a: boolean;
    winner_b: boolean;
};

type Props = {
    tournamentId: number;
    club: string;
    name: string;
    subtitle: string;
    courts: CourtVM[];
};

// Palette sombre « TV ».
const D = {
    bg: 'oklch(0.09 0.015 250)',
    panel: 'oklch(0.13 0.018 250)',
    line: 'oklch(0.22 0.018 250)',
    blue: 'oklch(0.42 0.16 240)',
    blueText: 'oklch(0.65 0.12 240)',
    mutedBlue: 'oklch(0.55 0.06 240)',
    orange: 'oklch(0.68 0.22 40)',
    green: 'oklch(0.60 0.14 152)',
    greenText: 'oklch(0.72 0.14 152)',
    dim: 'oklch(0.40 0.018 250)',
    score: 'oklch(0.60 0.04 250)',
    finishedName: 'oklch(0.75 0.05 250)',
};

function useClock(): string {
    const [time, setTime] = useState('');
    useEffect(() => {
        const tick = () =>
            setTime(
                new Date().toLocaleTimeString('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                }),
            );
        tick();
        const timer = setInterval(tick, 1000);

        return () => clearInterval(timer);
    }, []);

    return time;
}

function CourtCard({ court }: { court: CourtVM }) {
    const playing = court.status === 'playing';
    const finished = court.status === 'finished';

    return (
        <div
            className="flex flex-col overflow-hidden rounded-2xl"
            style={{
                background: playing
                    ? 'oklch(0.145 0.022 250)'
                    : finished
                      ? 'oklch(0.13 0.015 250)'
                      : 'oklch(0.11 0.010 250)',
                border: `1px solid ${playing ? D.blue : 'oklch(0.20 0.014 250)'}`,
                boxShadow: playing
                    ? '0 0 0 1px oklch(0.42 0.16 240 / 0.3), 0 4px 32px oklch(0.42 0.16 240 / 0.10)'
                    : 'none',
            }}
        >
            <div
                className="flex items-center justify-between px-4 pt-2.5 pb-2"
                style={{
                    borderBottom: `1px solid ${playing ? 'oklch(0.26 0.08 240)' : 'oklch(0.18 0.012 250)'}`,
                }}
            >
                <span
                    className="text-[13px] font-bold uppercase"
                    style={{
                        fontFamily: DISPLAY,
                        color: playing ? D.blueText : D.dim,
                        letterSpacing: '0.1em',
                    }}
                >
                    Terrain {court.label}
                </span>
                {playing && (
                    <span
                        className="flex items-center gap-1.5 rounded-full px-2 py-0.5"
                        style={{
                            background: 'oklch(0.52 0.14 152 / 0.15)',
                            border: '1px solid oklch(0.52 0.14 152 / 0.4)',
                        }}
                    >
                        <span
                            className="size-1.5 animate-pulse rounded-full"
                            style={{ background: D.green }}
                        />
                        <span
                            className="text-[11px] font-bold"
                            style={{ fontFamily: DISPLAY, color: D.greenText, letterSpacing: '0.07em' }}
                        >
                            EN JEU
                        </span>
                    </span>
                )}
                {finished && (
                    <span
                        className="rounded-full px-2 py-0.5 text-[11px] font-bold"
                        style={{
                            fontFamily: DISPLAY,
                            color: D.orange,
                            background: 'oklch(0.68 0.22 40 / 0.12)',
                            border: '1px solid oklch(0.68 0.22 40 / 0.35)',
                            letterSpacing: '0.07em',
                        }}
                    >
                        TERMINÉ
                    </span>
                )}
            </div>

            <div className="flex flex-1 flex-col items-center justify-center p-4 text-center">
                {playing && (
                    <div className="w-full">
                        <div
                            className="font-extrabold text-white"
                            style={{
                                fontFamily: DISPLAY,
                                fontSize: 'clamp(18px,2.4vw,30px)',
                                lineHeight: 1.15,
                                textWrap: 'balance',
                            }}
                        >
                            {court.team_a?.toUpperCase()}
                        </div>
                        <div
                            className="my-2.5 text-[13px] font-bold"
                            style={{ fontFamily: DISPLAY, color: D.blue, letterSpacing: '0.12em' }}
                        >
                            — VS —
                        </div>
                        <div
                            className="font-extrabold text-white"
                            style={{
                                fontFamily: DISPLAY,
                                fontSize: 'clamp(18px,2.4vw,30px)',
                                lineHeight: 1.15,
                                textWrap: 'balance',
                            }}
                        >
                            {court.team_b?.toUpperCase()}
                        </div>
                    </div>
                )}

                {finished && (
                    <div className="w-full">
                        <ScoreLine
                            name={court.team_a}
                            score={court.score_a}
                            winner={court.winner_a}
                        />
                        <div className="my-1.5 h-px" style={{ background: 'oklch(0.25 0.018 250)' }} />
                        <ScoreLine
                            name={court.team_b}
                            score={court.score_b}
                            winner={court.winner_b}
                        />
                    </div>
                )}

                {court.status === 'free' && (
                    <div className="py-2.5 text-center">
                        <div
                            className="mx-auto mb-2.5 flex size-9 items-center justify-center rounded-full"
                            style={{ border: '2px dashed oklch(0.28 0.015 250)' }}
                        >
                            <span
                                className="size-2.5 rounded-full"
                                style={{ background: 'oklch(0.28 0.015 250)' }}
                            />
                        </div>
                        <span
                            className="text-base font-bold uppercase"
                            style={{
                                fontFamily: DISPLAY,
                                color: 'oklch(0.35 0.015 250)',
                                letterSpacing: '0.12em',
                            }}
                        >
                            Libre
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
}

function ScoreLine({
    name,
    score,
    winner,
}: {
    name: string | null;
    score: number | null;
    winner: boolean;
}) {
    return (
        <div className="flex items-baseline justify-between gap-2.5">
            <span
                className="flex-1 text-left font-bold"
                style={{
                    fontFamily: DISPLAY,
                    fontSize: 'clamp(15px,1.8vw,22px)',
                    color: D.finishedName,
                    lineHeight: 1.2,
                }}
            >
                {name?.toUpperCase()}
            </span>
            <span
                className="shrink-0 tabular-nums"
                style={{
                    fontFamily: MONO,
                    fontSize: 'clamp(22px,2.8vw,36px)',
                    color: winner ? D.orange : D.score,
                }}
            >
                {score}
            </span>
        </div>
    );
}

export default function Screen({ tournamentId, club, name, subtitle, courts }: Props) {
    useTournamentEcho(tournamentId);
    const time = useClock();

    return (
        <div
            className="flex min-h-screen flex-col text-white"
            style={{ background: D.bg, fontFamily: "'DM Sans', ui-sans-serif, sans-serif" }}
        >
            <Head title={`${name} — Écran public`} />

            <header
                className="flex shrink-0 flex-wrap items-center justify-between gap-5 px-7 py-3.5"
                style={{ background: D.panel, borderBottom: `1px solid ${D.line}` }}
            >
                <div className="flex items-center gap-4">
                    <div
                        className="flex size-11 shrink-0 items-center justify-center rounded-full"
                        style={{ background: D.orange, boxShadow: '0 2px 16px oklch(0.68 0.22 40 / 0.3)' }}
                    >
                        <span className="text-lg" role="img" aria-label="boule">
                            🎯
                        </span>
                    </div>
                    <div>
                        <div
                            className="text-xs font-bold uppercase"
                            style={{ fontFamily: DISPLAY, color: D.mutedBlue, letterSpacing: '0.09em' }}
                        >
                            {club}
                        </div>
                        <div
                            className="text-[22px] font-extrabold text-white"
                            style={{ fontFamily: DISPLAY }}
                        >
                            {name}
                        </div>
                        <div className="text-xs" style={{ color: D.mutedBlue }}>
                            {subtitle}
                        </div>
                    </div>
                </div>
                <div className="flex items-center gap-5">
                    <span
                        className="flex items-center gap-2 rounded-full px-4 py-1.5"
                        style={{
                            background: 'oklch(0.93 0.06 152 / 0.12)',
                            border: '1px solid oklch(0.52 0.14 152 / 0.4)',
                        }}
                    >
                        <span
                            className="size-2 animate-pulse rounded-full"
                            style={{ background: D.green }}
                        />
                        <span
                            className="text-[15px] font-bold uppercase"
                            style={{ fontFamily: DISPLAY, color: D.greenText, letterSpacing: '0.07em' }}
                        >
                            En direct
                        </span>
                    </span>
                    <div className="text-right">
                        <div
                            className="text-[28px] leading-none text-white tabular-nums"
                            style={{ fontFamily: MONO, letterSpacing: '0.04em' }}
                        >
                            {time}
                        </div>
                        <div className="mt-0.5 text-[11px]" style={{ color: D.dim }}>
                            Mise à jour automatique
                        </div>
                    </div>
                </div>
            </header>

            <main className="grid flex-1 auto-rows-fr grid-cols-2 gap-4 p-5 md:grid-cols-3 xl:grid-cols-4">
                {courts.map((court, i) => (
                    <CourtCard key={i} court={court} />
                ))}
            </main>

            <footer
                className="flex shrink-0 items-center justify-between px-7 py-2.5"
                style={{ background: D.panel, borderTop: `1px solid ${D.line}` }}
            >
                <span className="text-xs" style={{ color: D.mutedBlue }}>
                    Résultats mis à jour automatiquement
                </span>
                <span className="text-xs" style={{ color: D.mutedBlue }}>
                    Suivre son équipe : scannez le QR de votre inscription
                </span>
            </footer>
        </div>
    );
}
