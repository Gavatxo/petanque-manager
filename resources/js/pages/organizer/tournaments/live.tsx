import { Head, router } from '@inertiajs/react';
import { Check, CircleDot, Clock, Flag, Play, Plus, Search, Trophy, X } from 'lucide-react';
import { useState } from 'react';
import { useTournamentEcho } from '@/hooks/use-tournament-echo';
import type { BreadcrumbItem } from '@/types';

type MatchVM = {
    id: number;
    round: number;
    team_a: string | null;
    team_b: string | null;
    team_a_id: number | null;
    team_b_id: number | null;
    team_a_number: number | null;
    team_b_number: number | null;
    court: string | null;
    score_a: number | null;
    score_b: number | null;
    winner_team_id: number | null;
    status: string;
    is_walkover: boolean;
    is_forfeit: boolean;
};

type RoundVM = { round: number; label?: string; matches: MatchVM[] };
type StandingVM = { team: string; seed: number; wins: number; losses: number };
type DivisionVM = {
    label: string;
    rounds: RoundVM[];
    ranking: { position: number | null; team: string }[];
    complete: boolean;
};

type Props = {
    tournament: {
        id: number;
        name: string;
        status_label: string;
        current_phase: string | null;
        points_target: number;
        team_format_label: string;
        qualifying_rounds: number;
        tableaux_count: number;
    };
    counts: { teams: number; courts: number };
    canStartQualification: boolean;
    formatSuggestion: {
        qualifying_rounds: number;
        tableaux_count: number;
        points_target: number;
    } | null;
    qualification: {
        currentRound: number;
        complete: boolean;
        rounds: RoundVM[];
        standings: StandingVM[];
    } | null;
    finals: DivisionVM[] | null;
};

// Palette « boulodrome » (identité pétanque).
const C = {
    bg: 'oklch(0.965 0.008 65)',
    card: 'oklch(1 0 0)',
    ink: 'oklch(0.16 0.015 250)',
    ink2: 'oklch(0.42 0.015 250)',
    muted: 'oklch(0.52 0.025 55)',
    border: 'oklch(0.87 0.018 65)',
    primary: 'oklch(0.42 0.16 240)',
    accent: 'oklch(0.68 0.22 40)',
    green: 'oklch(0.52 0.14 152)',
    greenBg: 'oklch(0.93 0.06 152)',
    greenText: 'oklch(0.30 0.12 152)',
    amber: 'oklch(0.70 0.18 80)',
    amberBg: 'oklch(0.96 0.055 80)',
    amberText: 'oklch(0.40 0.14 80)',
    neutral: 'oklch(0.76 0.014 65)',
    neutralBg: 'oklch(0.94 0.012 65)',
    neutralText: 'oklch(0.40 0.018 55)',
};
const DISPLAY = "'Barlow Condensed', sans-serif";
const MONO = "'DM Mono', ui-monospace, monospace";
const HIGHLIGHT_SHADOW = '0 0 0 3px oklch(0.68 0.22 40 / 0.30)';

function NumberBadge({ n }: { n: number | null }) {
    if (n === null) {
        return null;
    }

    return (
        <span
            className="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded px-1 text-xs font-bold tabular-nums"
            style={{ background: C.neutralBg, color: C.muted, fontFamily: MONO }}
        >
            {n}
        </span>
    );
}

function TeamName({
    name,
    number,
    dim,
}: {
    name: string | null;
    number: number | null;
    dim?: boolean;
}) {
    return (
        <span className="flex min-w-0 items-center gap-1.5">
            <NumberBadge n={number} />
            <span
                className="truncate"
                style={{ color: dim ? C.ink2 : C.ink, fontStyle: name ? 'normal' : 'italic' }}
            >
                {name ?? 'Adversaire à venir'}
            </span>
        </span>
    );
}

function ColumnHeader({
    label,
    count,
    color,
    bg,
    accent,
    icon,
}: {
    label: string;
    count: number;
    color: string;
    bg: string;
    accent: string;
    icon: React.ReactNode;
}) {
    return (
        <div
            className="flex shrink-0 items-center gap-2 px-4 py-2.5"
            style={{ background: bg, borderBottom: `2px solid ${accent}` }}
        >
            <span style={{ color }} className="flex items-center">
                {icon}
            </span>
            <span
                className="flex-1 text-[13px] font-bold uppercase"
                style={{ fontFamily: DISPLAY, color, letterSpacing: '0.07em' }}
            >
                {label}
            </span>
            <span
                className="flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-bold text-white tabular-nums"
                style={{ background: accent }}
            >
                {count}
            </span>
        </div>
    );
}

export default function LiveTournament({
    tournament,
    counts,
    canStartQualification,
    formatSuggestion,
    qualification,
    finals,
}: Props) {
    useTournamentEcho(tournament.id);

    const showUrl = `/organizer/tournaments/${tournament.id}`;
    // Format proposé au tirage selon le nombre d'équipes, librement ajustable.
    // Les points ne sont pas réglés ici : une partie de pétanque se joue en 13.
    const [format, setFormat] = useState({
        qualifying_rounds: formatSuggestion?.qualifying_rounds ?? tournament.qualifying_rounds,
        tableaux_count: formatSuggestion?.tableaux_count ?? tournament.tableaux_count,
    });
    const [scoring, setScoring] = useState<MatchVM | null>(null);
    const [mode, setMode] = useState<'record' | 'correct'>('record');
    const [scoreA, setScoreA] = useState(tournament.points_target);
    const [scoreB, setScoreB] = useState(0);
    const [forfeiting, setForfeiting] = useState<MatchVM | null>(null);
    const [search, setSearch] = useState('');
    const [highlightId, setHighlightId] = useState<number | null>(null);
    const [searchMsg, setSearchMsg] = useState<string | null>(null);

    const openScore = (match: MatchVM) => {
        setMode('record');
        setScoreA(tournament.points_target);
        setScoreB(0);
        setScoring(match);
    };
    const openCorrect = (match: MatchVM) => {
        setMode('correct');
        setScoreA(match.score_a ?? tournament.points_target);
        setScoreB(match.score_b ?? 0);
        setScoring(match);
    };
    const submitScore = () => {
        if (!scoring) {
            return;
        }

        const options = { preserveScroll: true, onSuccess: () => setScoring(null) };
        const payload = { score_a: scoreA, score_b: scoreB };

        if (mode === 'correct') {
            router.patch(`/organizer/matches/${scoring.id}/result`, payload, options);
        } else {
            router.post(`/organizer/matches/${scoring.id}/result`, payload, options);
        }
    };
    const submitForfeit = (teamId: number | null) => {
        if (!forfeiting || teamId === null) {
            return;
        }

        router.post(
            `/organizer/matches/${forfeiting.id}/forfeit`,
            { forfeiting_team_id: teamId },
            { preserveScroll: true, onSuccess: () => setForfeiting(null) },
        );
    };
    const post = (url: string) => router.post(url, {}, { preserveScroll: true });

    const clamp = (v: number) => Math.max(0, Math.min(tournament.points_target, v));
    const winnerWouldChange =
        mode === 'correct' &&
        scoring !== null &&
        scoring.winner_team_id !== null &&
        (scoreA > scoreB ? scoring.team_a_id : scoring.team_b_id) !== scoring.winner_team_id;

    const qualMatches = qualification?.rounds.flatMap((r) => r.matches) ?? [];
    const playing = qualMatches.filter((m) => m.status === 'playing');
    const waiting = qualMatches.filter((m) => m.status === 'pending' || m.status === 'ready');
    const done = qualMatches.filter((m) => m.status === 'finished' || m.status === 'bye');

    // Recherche rapide par numéro d'équipe : amène directement à son match
    // (priorité aux parties en cours, puis en attente, puis terminées).
    const runSearch = (raw: string) => {
        setSearch(raw);
        const num = Number.parseInt(raw.trim(), 10);

        if (!Number.isFinite(num)) {
            setSearchMsg(null);
            setHighlightId(null);

            return;
        }

        const match = [...playing, ...waiting, ...done].find(
            (m) => m.team_a_number === num || m.team_b_number === num,
        );

        if (!match) {
            setSearchMsg(`Aucune équipe n°${num} en jeu.`);
            setHighlightId(null);

            return;
        }

        setSearchMsg(null);
        setHighlightId(match.id);
        requestAnimationFrame(() => {
            document
                .getElementById(`qmatch-${match.id}`)
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    };

    return (
        <div
            className="flex h-[calc(100svh-5rem)] flex-col overflow-hidden"
            style={{ background: C.bg, fontFamily: "'DM Sans', ui-sans-serif, sans-serif" }}
        >
            <Head title={`Déroulé — ${tournament.name}`} />

            {/* Header */}
            <header
                className="flex shrink-0 flex-wrap items-start justify-between gap-4 px-5 py-3"
                style={{ background: C.card, borderBottom: `1px solid ${C.border}` }}
            >
                <div>
                    <h1
                        className="text-2xl font-extrabold"
                        style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.01em' }}
                    >
                        {tournament.name}
                    </h1>
                    <p className="text-xs" style={{ color: C.muted }}>
                        {tournament.team_format_label} · {tournament.qualifying_rounds} parties
                        qualificatives · {tournament.tableaux_count} tableau(x)
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    {qualification && (
                        <span className="text-xs" style={{ color: C.muted }}>
                            Ronde{' '}
                            <strong style={{ color: C.ink, fontFamily: MONO }}>
                                {qualification.currentRound}
                            </strong>
                            <span style={{ color: C.neutral }}>
                                {' '}
                                / {tournament.qualifying_rounds}
                            </span>
                        </span>
                    )}
                    {tournament.current_phase !== null &&
                        tournament.current_phase !== 'completed' && (
                            <span
                                className="inline-flex items-center gap-1.5 rounded-full px-3 py-1"
                                style={{ background: C.greenBg }}
                            >
                                <span
                                    className="size-1.5 animate-ping rounded-full"
                                    style={{ background: C.green }}
                                />
                                <span
                                    className="text-[11px] font-bold uppercase"
                                    style={{ color: C.greenText, letterSpacing: '0.05em' }}
                                >
                                    En direct
                                </span>
                            </span>
                        )}
                    {qualification?.complete && tournament.current_phase === 'qualification' && (
                        <button
                            onClick={() => post(`${showUrl}/finals/start`)}
                            className="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-semibold text-white"
                            style={{ background: C.accent }}
                        >
                            <Flag className="size-3.5" />
                            Lancer les phases finales
                        </button>
                    )}
                </div>
            </header>

            {/* Setup — tirage : format suggéré selon le nombre d'équipes, ajustable */}
            {tournament.current_phase === null && (
                <div className="flex flex-1 items-center justify-center p-6">
                    <div
                        className="w-full max-w-md rounded-2xl p-8"
                        style={{ background: C.card, border: `1px solid ${C.border}` }}
                    >
                        <div className="text-center">
                            <div
                                className="mx-auto mb-4 flex size-14 items-center justify-center rounded-full"
                                style={{ background: C.greenBg, color: C.green }}
                            >
                                <Play className="size-6" />
                            </div>
                            <h2
                                className="text-xl font-extrabold"
                                style={{ fontFamily: DISPLAY, color: C.ink }}
                            >
                                Prêt à lancer&nbsp;?
                            </h2>
                            <p className="mt-1 text-sm" style={{ color: C.muted }}>
                                {counts.teams} équipe(s) officielle(s) ·{' '}
                                {counts.courts > 0
                                    ? `${counts.courts} terrain(s)`
                                    : 'terrains non numérotés'}
                                .
                            </p>
                        </div>

                        {!canStartQualification ? (
                            <p className="mt-4 text-center text-sm" style={{ color: C.muted }}>
                                Il faut au moins 2 équipes officielles. Validez les présences puis
                                créez les équipes depuis les inscriptions. Les terrains sont
                                optionnels.
                            </p>
                        ) : (
                            <>
                                <div
                                    className="mt-6 rounded-xl p-4"
                                    style={{ background: C.bg, border: `1px solid ${C.border}` }}
                                >
                                    <div className="mb-3 flex items-center gap-1.5">
                                        <span
                                            className="text-[13px] font-bold uppercase"
                                            style={{
                                                fontFamily: DISPLAY,
                                                color: C.ink,
                                                letterSpacing: '0.05em',
                                            }}
                                        >
                                            Format suggéré
                                        </span>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2.5">
                                        <FormatStepper
                                            label="Parties qualif."
                                            value={format.qualifying_rounds}
                                            min={1}
                                            max={12}
                                            onChange={(v) =>
                                                setFormat((f) => ({
                                                    ...f,
                                                    qualifying_rounds: v,
                                                }))
                                            }
                                        />
                                        <FormatStepper
                                            label="Tableaux"
                                            value={format.tableaux_count}
                                            min={1}
                                            max={4}
                                            onChange={(v) =>
                                                setFormat((f) => ({ ...f, tableaux_count: v }))
                                            }
                                        />
                                    </div>
                                    <p className="mt-3 text-[11px]" style={{ color: C.muted }}>
                                        Proposé pour {counts.teams} équipe(s). Ajustez librement
                                        avant de lancer.
                                    </p>
                                </div>

                                <button
                                    onClick={() =>
                                        router.post(`${showUrl}/qualification/start`, format, {
                                            preserveScroll: true,
                                        })
                                    }
                                    className="mt-5 flex w-full items-center justify-center gap-2 rounded-lg px-5 py-3 text-sm font-bold text-white"
                                    style={{ background: C.accent }}
                                >
                                    <Play className="size-4" />
                                    Lancer les qualifications
                                </button>
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* Recherche rapide par numéro d'équipe */}
            {qualification && (
                <div
                    className="flex shrink-0 items-center gap-3 px-5 py-2.5"
                    style={{ background: C.card, borderBottom: `1px solid ${C.border}` }}
                >
                    <div className="relative w-full max-w-xs">
                        <Search
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                            style={{ color: C.muted }}
                        />
                        <input
                            value={search}
                            onChange={(e) => runSearch(e.target.value)}
                            inputMode="numeric"
                            placeholder="N° d’équipe → aller au match"
                            className="w-full rounded-lg py-2 pr-8 pl-9 text-sm outline-none focus:ring-2"
                            style={{
                                background: C.neutralBg,
                                border: `1px solid ${C.border}`,
                                color: C.ink,
                            }}
                        />
                        {search !== '' && (
                            <button
                                type="button"
                                onClick={() => runSearch('')}
                                aria-label="Effacer la recherche"
                                className="absolute top-1/2 right-2.5 -translate-y-1/2"
                                style={{ color: C.muted }}
                            >
                                <X className="size-4" />
                            </button>
                        )}
                    </div>
                    {searchMsg && (
                        <span className="text-xs font-medium" style={{ color: C.amberText }}>
                            {searchMsg}
                        </span>
                    )}
                </div>
            )}

            {/* Kanban qualifications */}
            {qualification && (
                <div className="grid min-h-0 flex-1 grid-cols-1 overflow-y-auto lg:grid-cols-[2fr_1.35fr_1.65fr] lg:grid-rows-1 lg:overflow-hidden">
                    {/* EN COURS */}
                    <div
                        className="flex flex-col overflow-hidden lg:min-h-0"
                        style={{ borderRight: `1px solid ${C.border}` }}
                    >
                        <ColumnHeader
                            label="En cours"
                            count={playing.length}
                            color={C.greenText}
                            bg={C.greenBg}
                            accent={C.green}
                            icon={<CircleDot className="size-3.5" />}
                        />
                        <div className="flex min-h-0 flex-1 flex-col gap-2.5 overflow-y-auto p-3">
                            {playing.map((m) => (
                                <div
                                    key={m.id}
                                    id={`qmatch-${m.id}`}
                                    className="overflow-hidden rounded-xl transition-shadow"
                                    style={{
                                        background: C.card,
                                        border: `1px solid ${highlightId === m.id ? C.accent : C.border}`,
                                        boxShadow: highlightId === m.id ? HIGHLIGHT_SHADOW : 'none',
                                    }}
                                >
                                    <div
                                        className="flex items-center gap-1.5 px-3 py-1.5"
                                        style={{ background: C.primary }}
                                    >
                                        <span
                                            className="text-xs font-bold text-white uppercase"
                                            style={{ fontFamily: DISPLAY, letterSpacing: '0.07em' }}
                                        >
                                            {m.court ? `Terrain ${m.court}` : 'En cours'}
                                        </span>
                                    </div>
                                    <div className="p-3">
                                        <div className="text-[15px] font-semibold">
                                            <TeamName name={m.team_a} number={m.team_a_number} />
                                        </div>
                                        <div className="flex items-center gap-1.5 py-1">
                                            <div
                                                className="h-px flex-1"
                                                style={{ background: C.border }}
                                            />
                                            <span
                                                className="text-[10px] font-bold"
                                                style={{ color: C.muted }}
                                            >
                                                VS
                                            </span>
                                            <div
                                                className="h-px flex-1"
                                                style={{ background: C.border }}
                                            />
                                        </div>
                                        <div className="text-[15px] font-semibold">
                                            <TeamName name={m.team_b} number={m.team_b_number} />
                                        </div>
                                        <button
                                            onClick={() => openScore(m)}
                                            className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg py-3 text-sm font-bold text-white"
                                            style={{ background: C.accent }}
                                        >
                                            <Plus className="size-3.5" />
                                            Saisir le score
                                        </button>
                                        <button
                                            onClick={() => setForfeiting(m)}
                                            className="mt-1.5 flex w-full items-center justify-center gap-1.5 py-1 text-xs font-medium"
                                            style={{ color: C.muted }}
                                        >
                                            <Flag className="size-3" />
                                            Déclarer forfait
                                        </button>
                                    </div>
                                </div>
                            ))}
                            {playing.length === 0 && (
                                <p className="p-4 text-center text-sm" style={{ color: C.muted }}>
                                    Aucune partie en cours.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* EN ATTENTE */}
                    <div
                        className="flex flex-col overflow-hidden lg:min-h-0"
                        style={{ borderRight: `1px solid ${C.border}` }}
                    >
                        <ColumnHeader
                            label="En attente"
                            count={waiting.length}
                            color={C.amberText}
                            bg={C.amberBg}
                            accent={C.amber}
                            icon={<Clock className="size-3.5" />}
                        />
                        <div className="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto p-3">
                            {waiting.map((m) => (
                                <div
                                    key={m.id}
                                    id={`qmatch-${m.id}`}
                                    className="rounded-lg p-3 transition-shadow"
                                    style={{
                                        background: C.card,
                                        border: `1px solid ${highlightId === m.id ? C.accent : C.border}`,
                                        boxShadow: highlightId === m.id ? HIGHLIGHT_SHADOW : 'none',
                                    }}
                                >
                                    <span
                                        className="mb-2 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                        style={{ background: C.amberBg, color: C.amberText }}
                                    >
                                        {m.team_b === null
                                            ? 'En attente d’un adversaire'
                                            : 'Couverte · terrain attendu'}
                                    </span>
                                    <div className="text-[13px] font-semibold">
                                        <TeamName name={m.team_a} number={m.team_a_number} />
                                    </div>
                                    <div
                                        className="py-0.5 text-[10px] italic"
                                        style={{ color: C.muted }}
                                    >
                                        — vs —
                                    </div>
                                    <div className="text-[13px]">
                                        <TeamName name={m.team_b} number={m.team_b_number} dim />
                                    </div>
                                </div>
                            ))}
                            {waiting.length === 0 && (
                                <p className="p-4 text-center text-sm" style={{ color: C.muted }}>
                                    Rien en attente.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* TERMINÉS + classement */}
                    <div className="flex flex-col overflow-hidden lg:min-h-0">
                        <ColumnHeader
                            label="Terminés"
                            count={done.length}
                            color={C.neutralText}
                            bg={C.neutralBg}
                            accent={C.neutral}
                            icon={<Check className="size-3.5" />}
                        />
                        <div className="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto p-3">
                            {done.map((m) => (
                                <div
                                    key={m.id}
                                    id={`qmatch-${m.id}`}
                                    className="rounded-lg p-3 transition-shadow"
                                    style={{
                                        background: C.card,
                                        border: `1px solid ${highlightId === m.id ? C.accent : C.border}`,
                                        boxShadow: highlightId === m.id ? HIGHLIGHT_SHADOW : 'none',
                                    }}
                                >
                                    <div
                                        className="mb-1.5 flex items-center justify-between text-[10px] font-semibold uppercase"
                                        style={{ color: C.muted }}
                                    >
                                        <span>{m.court ? `Terrain ${m.court}` : `Ronde ${m.round}`}</span>
                                        {m.is_forfeit && <span>Forfait</span>}
                                        <button
                                            onClick={() => openCorrect(m)}
                                            className="underline-offset-2 hover:underline"
                                        >
                                            Corriger
                                        </button>
                                    </div>
                                    <ScoreRow
                                        name={m.team_a}
                                        number={m.team_a_number}
                                        score={m.score_a}
                                        win={m.winner_team_id === m.team_a_id}
                                    />
                                    <ScoreRow
                                        name={m.team_b}
                                        number={m.team_b_number}
                                        score={m.score_b}
                                        win={m.winner_team_id === m.team_b_id}
                                    />
                                </div>
                            ))}

                            {qualification.standings.length > 0 && (
                                <div
                                    className="mt-0.5 overflow-hidden rounded-lg"
                                    style={{
                                        background: 'oklch(0.975 0.005 65)',
                                        border: `1px solid ${C.border}`,
                                    }}
                                >
                                    <div
                                        className="flex items-center gap-1.5 px-3 py-2"
                                        style={{ borderBottom: `1px solid ${C.border}` }}
                                    >
                                        <Trophy className="size-3" style={{ color: C.amber }} />
                                        <span
                                            className="text-xs font-bold uppercase"
                                            style={{ fontFamily: DISPLAY, color: C.neutralText }}
                                        >
                                            Classement provisoire
                                        </span>
                                    </div>
                                    <div className="py-1">
                                        {qualification.standings.map((s, i) => (
                                            <div
                                                key={i}
                                                className="flex items-center justify-between px-3 py-1 text-xs"
                                            >
                                                <span className="flex min-w-0 items-center gap-1.5">
                                                    <NumberBadge n={s.seed} />
                                                    <span
                                                        className="truncate"
                                                        style={{ color: C.ink }}
                                                    >
                                                        {s.team}
                                                    </span>
                                                </span>
                                                <span
                                                    className="flex shrink-0 gap-2"
                                                    style={{ fontFamily: MONO }}
                                                >
                                                    <span style={{ color: C.greenText }}>
                                                        {s.wins}V
                                                    </span>
                                                    <span style={{ color: C.muted }}>
                                                        {s.losses}D
                                                    </span>
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Phases finales */}
            {finals && (
                <div className="flex-1 space-y-4 overflow-y-auto p-5">
                    {finals.map((division) => (
                        <div
                            key={division.label}
                            className="rounded-xl p-4"
                            style={{ background: C.card, border: `1px solid ${C.border}` }}
                        >
                            <div className="mb-3 flex items-center gap-2">
                                <Trophy className="size-4" style={{ color: C.accent }} />
                                <span
                                    className="text-lg font-extrabold"
                                    style={{ fontFamily: DISPLAY, color: C.ink }}
                                >
                                    Tableau {division.label}
                                </span>
                                {division.complete && (
                                    <span
                                        className="rounded-full px-2 py-0.5 text-[10px] font-bold text-white uppercase"
                                        style={{ background: C.green }}
                                    >
                                        Terminé
                                    </span>
                                )}
                            </div>
                            <div className="flex gap-4 overflow-x-auto pb-2">
                                {division.rounds.map((round) => (
                                    <div key={round.round} className="w-56 shrink-0 space-y-2">
                                        <p
                                            className="text-xs font-bold uppercase"
                                            style={{ fontFamily: DISPLAY, color: C.muted }}
                                        >
                                            {round.label}
                                        </p>
                                        {round.matches.map((m) =>
                                            m.status === 'bye' ? (
                                                <div
                                                    key={m.id}
                                                    className="rounded-lg border border-dashed p-3 text-sm"
                                                    style={{ borderColor: C.border, color: C.muted }}
                                                >
                                                    {m.team_a ?? m.team_b} qualifié(e)
                                                </div>
                                            ) : (
                                                <div
                                                    key={m.id}
                                                    className="space-y-1 rounded-lg p-3"
                                                    style={{ border: `1px solid ${C.border}` }}
                                                >
                                                    <ScoreRow
                                                        name={m.team_a}
                                                        number={m.team_a_number}
                                                        score={m.score_a}
                                                        win={m.winner_team_id === m.team_a_id}
                                                    />
                                                    <ScoreRow
                                                        name={m.team_b}
                                                        number={m.team_b_number}
                                                        score={m.score_b}
                                                        win={m.winner_team_id === m.team_b_id}
                                                    />
                                                    {m.status === 'ready' &&
                                                        m.team_a &&
                                                        m.team_b && (
                                                            <button
                                                                onClick={() => openScore(m)}
                                                                className="mt-1 w-full rounded-md py-1.5 text-xs font-bold text-white"
                                                                style={{ background: C.accent }}
                                                            >
                                                                Saisir le score
                                                            </button>
                                                        )}
                                                    {m.status === 'finished' && (
                                                        <button
                                                            onClick={() => openCorrect(m)}
                                                            className="text-[10px] uppercase"
                                                            style={{ color: C.muted }}
                                                        >
                                                            Corriger
                                                        </button>
                                                    )}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                ))}
                            </div>
                            {division.complete && (
                                <div
                                    className="mt-4 rounded-lg p-3"
                                    style={{ background: C.neutralBg }}
                                >
                                    <p
                                        className="mb-2 text-sm font-bold"
                                        style={{ fontFamily: DISPLAY, color: C.ink }}
                                    >
                                        Classement final
                                    </p>
                                    <ol className="space-y-1 text-sm">
                                        {division.ranking.map((r, i) => (
                                            <li key={i} className="flex items-center gap-2">
                                                <span
                                                    className="w-6 tabular-nums"
                                                    style={{ color: C.muted, fontFamily: MONO }}
                                                >
                                                    {r.position ?? '—'}
                                                </span>
                                                <span
                                                    style={{
                                                        fontWeight: r.position === 1 ? 700 : 400,
                                                        color: C.ink,
                                                    }}
                                                >
                                                    {r.team}
                                                </span>
                                                {r.position === 1 && (
                                                    <Trophy
                                                        className="size-3.5"
                                                        style={{ color: C.accent }}
                                                    />
                                                )}
                                            </li>
                                        ))}
                                    </ol>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* Modale de saisie / correction */}
            {scoring && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-5"
                    style={{ background: 'oklch(0 0 0 / 0.52)', backdropFilter: 'blur(3px)' }}
                    onClick={(e) => e.target === e.currentTarget && setScoring(null)}
                >
                    <div
                        className="w-full max-w-md overflow-hidden rounded-2xl"
                        style={{ background: C.card }}
                    >
                        <div
                            className="px-6 pt-5 pb-4"
                            style={{ borderBottom: `1px solid ${C.border}` }}
                        >
                            <h2
                                className="text-xl font-extrabold"
                                style={{ fontFamily: DISPLAY, color: C.ink }}
                            >
                                {mode === 'correct' ? 'Corriger le score' : 'Saisir le score'}
                            </h2>
                            <p className="text-xs" style={{ color: C.muted }}>
                                Le vainqueur atteint {tournament.points_target} points.
                            </p>
                        </div>
                        <div className="grid grid-cols-[1fr_auto_1fr] items-start gap-3 px-6 py-6">
                            <Stepper
                                name={scoring.team_a}
                                number={scoring.team_a_number}
                                value={scoreA}
                                onChange={(v) => setScoreA(clamp(v))}
                            />
                            <div
                                className="pt-11 text-center text-xl font-bold"
                                style={{ fontFamily: DISPLAY, color: C.neutral }}
                            >
                                VS
                            </div>
                            <Stepper
                                name={scoring.team_b}
                                number={scoring.team_b_number}
                                value={scoreB}
                                onChange={(v) => setScoreB(clamp(v))}
                            />
                        </div>
                        {winnerWouldChange && (
                            <p
                                className="mx-6 mb-2 rounded-md p-2 text-center text-sm"
                                style={{ background: C.amberBg, color: C.amberText }}
                            >
                                Le vainqueur change : le concours sera recalculé.
                            </p>
                        )}
                        <div className="flex gap-2.5 px-6 pb-5">
                            <button
                                onClick={() => setScoring(null)}
                                className="flex-1 rounded-lg py-3 text-sm font-semibold"
                                style={{ background: C.neutralBg, color: C.ink }}
                            >
                                Annuler
                            </button>
                            <button
                                onClick={submitScore}
                                disabled={scoreA === scoreB}
                                className="flex flex-[2] items-center justify-center gap-2 rounded-lg py-3 text-sm font-bold text-white disabled:opacity-40"
                                style={{ background: C.accent }}
                            >
                                <Check className="size-4" />
                                {mode === 'correct' ? 'Corriger' : 'Valider le score'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Modale forfait */}
            {forfeiting && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-5"
                    style={{ background: 'oklch(0 0 0 / 0.52)', backdropFilter: 'blur(3px)' }}
                    onClick={(e) => e.target === e.currentTarget && setForfeiting(null)}
                >
                    <div
                        className="w-full max-w-sm overflow-hidden rounded-2xl p-6"
                        style={{ background: C.card }}
                    >
                        <h2
                            className="text-xl font-extrabold"
                            style={{ fontFamily: DISPLAY, color: C.ink }}
                        >
                            Déclarer un forfait
                        </h2>
                        <p className="mb-4 text-xs" style={{ color: C.muted }}>
                            Quelle équipe déclare forfait&nbsp;? L’adversaire l’emporte{' '}
                            {tournament.points_target}-0.
                        </p>
                        <div className="grid gap-2">
                            <button
                                onClick={() => submitForfeit(forfeiting.team_a_id)}
                                className="rounded-lg py-2.5 text-sm font-semibold"
                                style={{ border: `1px solid ${C.border}`, color: C.ink }}
                            >
                                {forfeiting.team_a} déclare forfait
                            </button>
                            <button
                                onClick={() => submitForfeit(forfeiting.team_b_id)}
                                className="rounded-lg py-2.5 text-sm font-semibold"
                                style={{ border: `1px solid ${C.border}`, color: C.ink }}
                            >
                                {forfeiting.team_b} déclare forfait
                            </button>
                            <button
                                onClick={() => setForfeiting(null)}
                                className="py-2 text-sm"
                                style={{ color: C.muted }}
                            >
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

function ScoreRow({
    name,
    number,
    score,
    win,
}: {
    name: string | null;
    number: number | null;
    score: number | null;
    win: boolean;
}) {
    return (
        <div className="flex items-baseline justify-between gap-2">
            <span
                className="flex min-w-0 flex-1 items-center gap-1.5 text-[13px]"
                style={{ fontWeight: win ? 700 : 400 }}
            >
                <NumberBadge n={number} />
                <span className="truncate" style={{ color: win ? C.greenText : C.ink }}>
                    {name}
                </span>
                {win && <Check className="size-3.5 shrink-0" style={{ color: C.green }} />}
            </span>
            {score !== null && (
                <span
                    className="shrink-0 text-base tabular-nums"
                    style={{ fontFamily: MONO, color: C.ink, fontWeight: win ? 500 : 400 }}
                >
                    {score}
                </span>
            )}
        </div>
    );
}

function Stepper({
    name,
    number,
    value,
    onChange,
}: {
    name: string | null;
    number: number | null;
    value: number;
    onChange: (v: number) => void;
}) {
    const btn =
        'flex size-10 items-center justify-center rounded-full text-2xl leading-none font-light';

    return (
        <div className="flex flex-col items-center gap-2.5">
            <div
                className="flex items-center gap-1 text-center text-[13px] font-semibold"
                style={{ color: C.ink }}
            >
                <NumberBadge n={number} />
                <span className="line-clamp-2">{name}</span>
            </div>
            <div className="flex items-center gap-2">
                <button
                    onClick={() => onChange(value - 1)}
                    className={btn}
                    style={{ background: C.neutralBg, border: `1px solid ${C.border}`, color: C.ink }}
                >
                    −
                </button>
                <span
                    className="min-w-[48px] text-center text-4xl tabular-nums"
                    style={{ fontFamily: MONO, color: C.ink }}
                >
                    {value}
                </span>
                <button
                    onClick={() => onChange(value + 1)}
                    className={btn}
                    style={{ background: C.neutralBg, border: `1px solid ${C.border}`, color: C.ink }}
                >
                    +
                </button>
            </div>
        </div>
    );
}

function FormatStepper({
    label,
    value,
    min,
    max,
    onChange,
}: {
    label: string;
    value: number;
    min: number;
    max: number;
    onChange: (v: number) => void;
}) {
    const clamp = (v: number) => Math.max(min, Math.min(max, v));
    const btn =
        'flex size-7 items-center justify-center rounded-md text-lg leading-none font-light disabled:opacity-30';

    return (
        <div className="flex flex-col items-center gap-1.5">
            <span className="text-center text-[11px] font-medium" style={{ color: C.muted }}>
                {label}
            </span>
            <div className="flex items-center gap-1">
                <button
                    type="button"
                    aria-label={`Diminuer ${label}`}
                    onClick={() => onChange(clamp(value - 1))}
                    disabled={value <= min}
                    className={btn}
                    style={{ background: C.neutralBg, border: `1px solid ${C.border}`, color: C.ink }}
                >
                    −
                </button>
                <span
                    className="min-w-8 text-center text-xl tabular-nums"
                    style={{ fontFamily: MONO, color: C.ink }}
                >
                    {value}
                </span>
                <button
                    type="button"
                    aria-label={`Augmenter ${label}`}
                    onClick={() => onChange(clamp(value + 1))}
                    disabled={value >= max}
                    className={btn}
                    style={{ background: C.neutralBg, border: `1px solid ${C.border}`, color: C.ink }}
                >
                    +
                </button>
            </div>
        </div>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Déroulé', href: '#' },
];

LiveTournament.layout = { breadcrumbs };
