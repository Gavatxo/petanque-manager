import { Head, router } from '@inertiajs/react';
import { Check, ChevronRight, Flag, Play, Search, Trophy, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTournamentEcho } from '@/hooks/use-tournament-echo';
import { BODY, C, DISPLAY, MONO } from '@/lib/petanque';
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
type StandingVM = {
    team: string;
    seed: number;
    wins: number;
    losses: number;
    points_for: number;
    points_against: number;
};
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

const GREEN_DOT = 'oklch(0.52 0.14 152)';

function LiveDot({ color, pulse }: { color: string; pulse?: boolean }) {
    return (
        <span
            className={`size-2 shrink-0 rounded-full ${pulse ? 'animate-pulse' : ''}`}
            style={{ background: color }}
        />
    );
}

function RecordChip({ wins, losses, won }: { wins: number; losses: number; won: boolean }) {
    return (
        <span
            className="rounded-lg px-1.5 text-[10px] font-bold"
            style={{
                background: won ? C.greenBg : C.neutralBg,
                color: won ? C.greenText : C.muted,
            }}
        >
            {wins}V {losses}D
        </span>
    );
}

function ScoreStepper({
    value,
    onChange,
    max,
}: {
    value: number;
    onChange: (v: number) => void;
    max: number;
}) {
    const clamp = (v: number) => Math.max(0, Math.min(max, v));
    const btn =
        'flex size-9 items-center justify-center rounded-full text-xl leading-none font-light';

    return (
        <div className="flex items-center gap-1.5">
            <button
                type="button"
                onClick={() => onChange(clamp(value - 1))}
                className={btn}
                style={{ background: C.neutralBg, border: `1px solid ${C.border}`, color: C.ink }}
            >
                −
            </button>
            <span
                className="min-w-[46px] text-center text-3xl tabular-nums"
                style={{ fontFamily: MONO, color: C.ink }}
            >
                {value}
            </span>
            <button
                type="button"
                onClick={() => onChange(clamp(value + 1))}
                className={btn}
                style={{ background: C.neutralBg, border: `1px solid ${C.border}`, color: C.ink }}
            >
                +
            </button>
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
    const maxScore = tournament.points_target;
    const notStarted = tournament.current_phase === null;
    const finalsAvailable = finals !== null && finals.length > 0;

    // Setup (tirage) — format suggéré, ajustable. Les points restent à 13.
    const [format, setFormat] = useState({
        qualifying_rounds: formatSuggestion?.qualifying_rounds ?? tournament.qualifying_rounds,
        tableaux_count: formatSuggestion?.tableaux_count ?? tournament.tableaux_count,
    });

    const roundsData = useMemo(() => qualification?.rounds ?? [], [qualification]);
    const currentRound = qualification?.currentRound ?? 0;
    const standings = useMemo(() => qualification?.standings ?? [], [qualification]);

    // Onglet actif : par défaut la ronde en cours (ou les finales si lancées).
    const [activeTab, setActiveTab] = useState<string>(
        finalsAvailable ? 'finals' : `round:${Math.max(1, currentRound)}`,
    );
    const [activeDivision, setActiveDivision] = useState<string>(finals?.[0]?.label ?? 'A');
    const [search, setSearch] = useState('');

    // Saisie de score en ligne (qualifications).
    const [expandedId, setExpandedId] = useState<number | null>(null);
    const [expandMode, setExpandMode] = useState<'record' | 'correct'>('record');
    const [scoreA, setScoreA] = useState(maxScore);
    const [scoreB, setScoreB] = useState(0);

    // Forfait + modale de score des phases finales.
    const [forfeiting, setForfeiting] = useState<MatchVM | null>(null);
    const [finalScoring, setFinalScoring] = useState<MatchVM | null>(null);
    const [finalMode, setFinalMode] = useState<'record' | 'correct'>('record');
    const [fScoreA, setFScoreA] = useState(maxScore);
    const [fScoreB, setFScoreB] = useState(0);

    const recordBySeed = useMemo(() => {
        const map = new Map<number, { wins: number; losses: number }>();
        standings.forEach((s) => map.set(s.seed, { wins: s.wins, losses: s.losses }));

        return map;
    }, [standings]);

    const allQual = roundsData.flatMap((r) => r.matches);
    const enCours = allQual.filter((m) => m.status === 'playing').length;
    const enAttente = allQual.filter((m) => m.status === 'pending' || m.status === 'ready').length;

    const isFinished = (m: MatchVM) => m.status === 'finished' || m.status === 'bye';

    const openExpand = (m: MatchVM) => {
        const correcting = m.status === 'finished';
        setExpandMode(correcting ? 'correct' : 'record');
        setScoreA(correcting ? (m.score_a ?? maxScore) : maxScore);
        setScoreB(correcting ? (m.score_b ?? 0) : 0);
        setExpandedId((id) => (id === m.id ? null : m.id));
    };
    const submitExpand = (m: MatchVM) => {
        const payload = { score_a: scoreA, score_b: scoreB };
        const opts = { preserveScroll: true, onSuccess: () => setExpandedId(null) };

        if (expandMode === 'correct') {
            router.patch(`/organizer/matches/${m.id}/result`, payload, opts);
        } else {
            router.post(`/organizer/matches/${m.id}/result`, payload, opts);
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
    const openFinal = (m: MatchVM) => {
        const correcting = m.status === 'finished';
        setFinalMode(correcting ? 'correct' : 'record');
        setFScoreA(correcting ? (m.score_a ?? maxScore) : maxScore);
        setFScoreB(correcting ? (m.score_b ?? 0) : 0);
        setFinalScoring(m);
    };
    const submitFinal = () => {
        if (!finalScoring) {
            return;
        }

        const payload = { score_a: fScoreA, score_b: fScoreB };
        const opts = { preserveScroll: true, onSuccess: () => setFinalScoring(null) };

        if (finalMode === 'correct') {
            router.patch(`/organizer/matches/${finalScoring.id}/result`, payload, opts);
        } else {
            router.post(`/organizer/matches/${finalScoring.id}/result`, payload, opts);
        }
    };

    // ── Header ───────────────────────────────────────────────────────────────
    const header = (
        <header
            className="flex shrink-0 flex-wrap items-center justify-between gap-3 px-5 py-3"
            style={{ background: C.card, borderBottom: `1px solid ${C.border}` }}
        >
            <div>
                <div className="text-[11px]" style={{ color: C.muted }}>
                    Concours → Déroulé
                </div>
                <h1
                    className="text-[21px] font-extrabold"
                    style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.01em' }}
                >
                    {tournament.name}
                </h1>
            </div>
            <div className="flex items-center gap-3">
                <span className="text-xs" style={{ color: C.muted }}>
                    {tournament.team_format_label} · {maxScore} pts ·{' '}
                    {counts.courts > 0 ? `${counts.courts} terrains` : 'terrains libres'}
                </span>
                {!notStarted && tournament.current_phase !== 'completed' && (
                    <span
                        className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1"
                        style={{ background: C.greenBg }}
                    >
                        <LiveDot color={GREEN_DOT} pulse />
                        <span
                            className="text-[11px] font-bold uppercase"
                            style={{ color: C.greenText, letterSpacing: '0.04em' }}
                        >
                            En cours
                        </span>
                    </span>
                )}
                {tournament.current_phase === 'completed' && (
                    <span
                        className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold uppercase"
                        style={{ background: C.neutralBg, color: C.neutralText, letterSpacing: '0.04em' }}
                    >
                        Terminé
                    </span>
                )}
            </div>
        </header>
    );

    // ── Not started : setup card (tirage) ────────────────────────────────────
    if (notStarted) {
        return (
            <div
                className="flex h-[calc(100svh-5rem)] flex-col overflow-hidden"
                style={{ background: C.bg, fontFamily: BODY }}
            >
                <Head title={`Déroulé — ${tournament.name}`} />
                {header}
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
                                                setFormat((f) => ({ ...f, qualifying_rounds: v }))
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
            </div>
        );
    }

    // ── Tabs ─────────────────────────────────────────────────────────────────
    const roundTabs = Array.from({ length: tournament.qualifying_rounds }, (_, i) => {
        const r = i + 1;
        const rd = roundsData.find((x) => x.round === r);
        const done = rd ? rd.matches.filter(isFinished).length : 0;
        const total = rd ? rd.matches.length : 0;

        return { key: `round:${r}`, label: `Partie ${r}`, badge: rd ? `${done}/${total}` : null };
    });

    const activeRound = activeTab.startsWith('round:') ? Number(activeTab.slice(6)) : null;

    const tabButton = (key: string, label: string, badge: string | null, opts?: { locked?: boolean }) => {
        const active = activeTab === key;
        const locked = opts?.locked;

        return (
            <button
                key={key}
                onClick={() => !locked && setActiveTab(key)}
                disabled={locked}
                className="flex shrink-0 items-center gap-1.5 px-4 py-3 text-[13px] whitespace-nowrap"
                style={{
                    borderBottom: `2px solid ${active ? C.primary : 'transparent'}`,
                    fontWeight: active ? 700 : 500,
                    color: locked ? C.neutral : active ? C.ink : C.muted,
                    cursor: locked ? 'not-allowed' : 'pointer',
                }}
            >
                {label}
                {badge && (
                    <span
                        className="rounded-full px-1.5 text-[10px] font-bold tabular-nums"
                        style={{
                            background: active ? C.greenBg : C.neutralBg,
                            color: active ? C.greenText : C.muted,
                        }}
                    >
                        {badge}
                    </span>
                )}
            </button>
        );
    };

    const tabBar = (
        <div
            className="flex shrink-0 items-stretch justify-between overflow-x-auto"
            style={{ background: C.card, borderBottom: `1px solid ${C.border}` }}
        >
            <div className="flex items-stretch">
                {roundTabs.map((t) => tabButton(t.key, t.label, t.badge))}
                {tabButton('finals', '🏆 Phases finales', null, { locked: !finalsAvailable })}
                {tabButton('standings', 'Classement', null)}
            </div>
            <div className="flex shrink-0 items-center gap-3 px-4">
                <span className="flex items-center gap-1.5">
                    <LiveDot color={GREEN_DOT} pulse />
                    <span
                        className="text-xs font-bold tabular-nums"
                        style={{ color: C.greenText }}
                    >
                        {enCours} en cours
                    </span>
                </span>
                <span className="h-4 w-px" style={{ background: C.border }} />
                <span className="flex items-center gap-1.5">
                    <LiveDot color={C.amber} />
                    <span
                        className="text-xs font-semibold tabular-nums"
                        style={{ color: C.amberText }}
                    >
                        {enAttente} en attente
                    </span>
                </span>
            </div>
        </div>
    );

    // ── Qualification round view ─────────────────────────────────────────────
    const renderRound = (r: number) => {
        const rd = roundsData.find((x) => x.round === r);

        if (!rd) {
            return (
                <div className="flex flex-1 items-center justify-center p-8 text-center">
                    <p className="max-w-sm text-sm" style={{ color: C.muted }}>
                        La Partie {r} sera générée automatiquement à la fin de la Partie {r - 1}.
                    </p>
                </div>
            );
        }

        const done = rd.matches.filter(isFinished).length;
        const total = rd.matches.length;
        const complete = total > 0 && done === total;
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        const q = search.trim().toLowerCase();
        const filtered = q
            ? rd.matches.filter(
                  (m) =>
                      (m.team_a ?? '').toLowerCase().includes(q) ||
                      (m.team_b ?? '').toLowerCase().includes(q) ||
                      String(m.team_a_number ?? '').includes(q) ||
                      String(m.team_b_number ?? '').includes(q),
              )
            : rd.matches;

        const nextRoundExists = roundsData.some((x) => x.round === r + 1);
        const isLastRound = r === tournament.qualifying_rounds;

        let cta: React.ReactNode = null;

        if (complete) {
            if (!isLastRound && nextRoundExists) {
                cta = (
                    <button
                        onClick={() => setActiveTab(`round:${r + 1}`)}
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-4 py-2 text-[13px] font-bold text-white"
                        style={{ background: C.accent }}
                    >
                        Passer à la Partie {r + 1}
                        <ChevronRight className="size-3.5" />
                    </button>
                );
            } else if (isLastRound && qualification?.complete) {
                cta = finalsAvailable ? (
                    <button
                        onClick={() => setActiveTab('finals')}
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-4 py-2 text-[13px] font-bold text-white"
                        style={{ background: C.accent }}
                    >
                        🏆 Voir les phases finales
                        <ChevronRight className="size-3.5" />
                    </button>
                ) : (
                    <button
                        onClick={() =>
                            router.post(`${showUrl}/finals/start`, {}, { preserveScroll: true })
                        }
                        className="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-4 py-2 text-[13px] font-bold text-white"
                        style={{ background: C.accent }}
                    >
                        <Flag className="size-3.5" />
                        Lancer les phases finales
                    </button>
                );
            }
        }

        return (
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Search + progress */}
                <div
                    className="flex shrink-0 items-center gap-3 px-4 py-2.5"
                    style={{ background: C.card, borderBottom: `1px solid ${C.border}` }}
                >
                    <div className="relative w-full max-w-xs">
                        <Search
                            className="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2"
                            style={{ color: C.muted }}
                        />
                        <input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher équipe, n° ou nom…"
                            className="w-full rounded-lg py-2 pr-8 pl-9 text-[13px] outline-none"
                            style={{
                                background: C.cardAlt,
                                border: `1.5px solid ${C.border}`,
                                color: C.ink,
                            }}
                        />
                        {search !== '' && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                aria-label="Effacer"
                                className="absolute top-1/2 right-2.5 -translate-y-1/2"
                                style={{ color: C.muted }}
                            >
                                <X className="size-4" />
                            </button>
                        )}
                    </div>
                    <div className="flex flex-1 items-center gap-2.5">
                        <div
                            className="h-1.5 flex-1 overflow-hidden rounded-full"
                            style={{ background: C.borderSoft }}
                        >
                            <div
                                className="h-full rounded-full transition-all"
                                style={{ width: `${pct}%`, background: C.green }}
                            />
                        </div>
                        <span
                            className="text-xs font-semibold whitespace-nowrap tabular-nums"
                            style={{ color: C.greenText }}
                        >
                            {done} / {total} matchs
                        </span>
                    </div>
                    {cta}
                </div>

                {/* Match list */}
                <div className="flex flex-1 flex-col gap-2 overflow-y-auto p-4">
                    {filtered.length === 0 && (
                        <p className="p-6 text-center text-sm" style={{ color: C.muted }}>
                            Aucun match ne correspond.
                        </p>
                    )}
                    {filtered.map((m) => renderMatchRow(m))}
                </div>
            </div>
        );
    };

    const renderMatchRow = (m: MatchVM) => {
        const playing = m.status === 'playing';
        const finished = isFinished(m);
        const expanded = expandedId === m.id;
        const recA = m.team_a_number !== null ? recordBySeed.get(m.team_a_number) : undefined;
        const recB = m.team_b_number !== null ? recordBySeed.get(m.team_b_number) : undefined;
        const wonA = m.winner_team_id !== null && m.winner_team_id === m.team_a_id;
        const wonB = m.winner_team_id !== null && m.winner_team_id === m.team_b_id;

        return (
            <div
                key={m.id}
                id={`qmatch-${m.id}`}
                className="overflow-hidden rounded-xl"
                style={{
                    background: C.card,
                    border: `1.5px solid ${playing ? 'oklch(0.80 0.08 152)' : C.border}`,
                }}
            >
                <div className="flex items-center gap-2.5 px-3.5 py-2.5">
                    {/* Terrain + status */}
                    <div className="flex w-[74px] shrink-0 items-center gap-1.5">
                        <LiveDot
                            color={
                                playing ? GREEN_DOT : finished ? C.neutral : C.amber
                            }
                            pulse={playing}
                        />
                        <span
                            className="text-[13px] font-bold"
                            style={{
                                fontFamily: DISPLAY,
                                color: playing ? C.greenText : C.muted,
                                letterSpacing: '0.04em',
                            }}
                        >
                            {m.court ? `T.${m.court}` : '—'}
                        </span>
                    </div>

                    {/* Team A */}
                    <div className="min-w-0 flex-1 text-right">
                        <div
                            className="truncate text-sm font-semibold"
                            style={{ color: C.ink, fontStyle: m.team_a ? 'normal' : 'italic' }}
                        >
                            {m.team_a ?? 'À venir'}
                        </div>
                        <div className="mt-0.5 flex items-center justify-end gap-1">
                            {m.team_a_number !== null && (
                                <span className="text-[10px]" style={{ color: C.muted }}>
                                    Éq.{m.team_a_number}
                                </span>
                            )}
                            {recA && <RecordChip wins={recA.wins} losses={recA.losses} won={wonA} />}
                        </div>
                    </div>

                    {/* Score / VS */}
                    <div className="w-[72px] shrink-0 text-center">
                        {finished && m.score_a !== null ? (
                            <span
                                className="text-lg tabular-nums"
                                style={{ fontFamily: MONO, color: C.ink }}
                            >
                                {m.score_a} — {m.score_b}
                            </span>
                        ) : (
                            <span
                                className="text-sm font-bold"
                                style={{ fontFamily: DISPLAY, color: C.neutral, letterSpacing: '0.08em' }}
                            >
                                VS
                            </span>
                        )}
                    </div>

                    {/* Team B */}
                    <div className="min-w-0 flex-1 text-left">
                        <div
                            className="truncate text-sm font-semibold"
                            style={{ color: C.ink, fontStyle: m.team_b ? 'normal' : 'italic' }}
                        >
                            {m.team_b ?? 'À venir'}
                        </div>
                        <div className="mt-0.5 flex items-center gap-1">
                            {m.team_b_number !== null && (
                                <span className="text-[10px]" style={{ color: C.muted }}>
                                    Éq.{m.team_b_number}
                                </span>
                            )}
                            {recB && <RecordChip wins={recB.wins} losses={recB.losses} won={wonB} />}
                        </div>
                    </div>

                    {/* Action */}
                    <div className="shrink-0">
                        {m.status === 'finished' ? (
                            <button
                                onClick={() => openExpand(m)}
                                className="flex items-center gap-1 rounded-md px-2.5 py-1.5 text-[11px] font-bold"
                                style={{ background: C.greenBg, color: C.greenText }}
                                title="Corriger le score"
                            >
                                <Check className="size-3" />
                                Validé
                            </button>
                        ) : m.status === 'bye' ? (
                            <span className="text-[11px] italic" style={{ color: C.muted }}>
                                Qualifié
                            </span>
                        ) : playing ? (
                            <button
                                onClick={() => openExpand(m)}
                                className="rounded-lg px-3.5 py-2 text-xs font-bold text-white"
                                style={{ background: C.primary }}
                            >
                                Saisir
                            </button>
                        ) : (
                            <span className="text-[11px] italic" style={{ color: C.amberText }}>
                                {m.team_b === null ? 'Adversaire à venir' : 'En attente'}
                            </span>
                        )}
                    </div>
                </div>

                {/* Expanded score entry */}
                {expanded && (playing || m.status === 'finished') && (
                    <div
                        className="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5"
                        style={{ borderTop: `1px solid ${C.borderSoft}`, background: C.cardAlt }}
                    >
                        <div className="flex flex-1 items-center justify-end gap-2">
                            <span
                                className="max-w-[120px] truncate text-xs font-semibold"
                                style={{ color: C.ink2 }}
                            >
                                {m.team_a}
                            </span>
                            <ScoreStepper value={scoreA} onChange={setScoreA} max={maxScore} />
                        </div>
                        <span
                            className="text-base font-bold"
                            style={{ fontFamily: DISPLAY, color: C.neutral }}
                        >
                            —
                        </span>
                        <div className="flex flex-1 items-center gap-2">
                            <ScoreStepper value={scoreB} onChange={setScoreB} max={maxScore} />
                            <span
                                className="max-w-[120px] truncate text-xs font-semibold"
                                style={{ color: C.ink2 }}
                            >
                                {m.team_b}
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            {playing && (
                                <button
                                    onClick={() => setForfeiting(m)}
                                    className="rounded-lg px-2.5 py-2 text-[11px] font-medium"
                                    style={{ color: C.muted }}
                                >
                                    Forfait
                                </button>
                            )}
                            <button
                                onClick={() => setExpandedId(null)}
                                className="rounded-lg px-3 py-2 text-xs font-semibold"
                                style={{ background: C.neutralBg, color: C.ink }}
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => submitExpand(m)}
                                disabled={scoreA === scoreB}
                                className="flex items-center gap-1.5 rounded-lg px-4 py-2 text-xs font-bold text-white disabled:opacity-40"
                                style={{ background: C.green }}
                            >
                                <Check className="size-3.5" />
                                {expandMode === 'correct' ? 'Corriger' : 'Valider'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        );
    };

    // ── Finals (brackets) ────────────────────────────────────────────────────
    const renderFinals = () => {
        const division = finals?.find((d) => d.label === activeDivision) ?? finals?.[0];

        if (!division) {
            return null;
        }

        const teamCount = division.rounds[0]
            ? division.rounds[0].matches.reduce(
                  (n, m) => n + (m.team_a ? 1 : 0) + (m.team_b ? 1 : 0),
                  0,
              )
            : 0;

        return (
            <div className="flex flex-1 flex-col overflow-hidden">
                {/* Division sub-tabs */}
                <div
                    className="flex shrink-0 flex-wrap items-center gap-2 px-4 py-2"
                    style={{ background: C.cardAlt, borderBottom: `1px solid ${C.border}` }}
                >
                    {finals?.map((d) => {
                        const active = d.label === activeDivision;

                        return (
                            <button
                                key={d.label}
                                onClick={() => setActiveDivision(d.label)}
                                className="rounded-lg px-4 py-1.5 text-[13px] font-bold uppercase"
                                style={{
                                    fontFamily: DISPLAY,
                                    letterSpacing: '0.05em',
                                    background: active ? C.primary : C.card,
                                    color: active ? '#fff' : C.ink2,
                                    border: `1.5px solid ${active ? C.primary : C.border}`,
                                }}
                            >
                                Tableau {d.label}
                            </button>
                        );
                    })}
                    <span className="ml-auto text-xs" style={{ color: C.muted }}>
                        {teamCount} équipes · {division.rounds.length} tour(s)
                    </span>
                </div>

                {/* Bracket */}
                <div className="flex-1 overflow-auto p-5">
                    <div className="flex min-w-max items-start gap-6">
                        {division.rounds.map((round) => (
                            <div key={round.round} className="flex w-56 shrink-0 flex-col gap-3">
                                <p
                                    className="text-center text-xs font-bold uppercase"
                                    style={{
                                        fontFamily: DISPLAY,
                                        color: C.muted,
                                        letterSpacing: '0.08em',
                                    }}
                                >
                                    {round.label ?? `Tour ${round.round}`}
                                </p>
                                {round.matches.map((m) => renderBracketMatch(m, division.label))}
                            </div>
                        ))}

                        {division.complete && division.ranking.length > 0 && (
                            <div
                                className="w-60 shrink-0 rounded-xl p-4"
                                style={{ background: C.card, border: `1px solid ${C.border}` }}
                            >
                                <div className="mb-2 flex items-center gap-1.5">
                                    <Trophy className="size-4" style={{ color: C.accent }} />
                                    <span
                                        className="text-sm font-bold uppercase"
                                        style={{ fontFamily: DISPLAY, color: C.ink }}
                                    >
                                        Classement final
                                    </span>
                                </div>
                                <ol className="space-y-1 text-sm">
                                    {division.ranking.map((rk, i) => (
                                        <li key={i} className="flex items-center gap-2">
                                            <span
                                                className="w-5 tabular-nums"
                                                style={{ color: C.muted, fontFamily: MONO }}
                                            >
                                                {rk.position ?? '—'}
                                            </span>
                                            <span
                                                style={{
                                                    color: C.ink,
                                                    fontWeight: rk.position === 1 ? 700 : 400,
                                                }}
                                            >
                                                {rk.team}
                                            </span>
                                            {rk.position === 1 && (
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
                </div>
            </div>
        );
    };

    const renderBracketMatch = (m: MatchVM, divisionLabel: string) => {
        if (m.status === 'bye') {
            return (
                <div
                    key={m.id}
                    className="rounded-lg border border-dashed p-3 text-sm"
                    style={{ borderColor: C.border, color: C.muted }}
                >
                    {m.team_a ?? m.team_b} qualifié(e)
                </div>
            );
        }

        const finished = m.status === 'finished';
        const ready = m.status === 'ready' && m.team_a !== null && m.team_b !== null;
        const wonA = m.winner_team_id !== null && m.winner_team_id === m.team_a_id;
        const wonB = m.winner_team_id !== null && m.winner_team_id === m.team_b_id;

        return (
            <div
                key={m.id}
                className="overflow-hidden rounded-lg"
                style={{
                    background: C.card,
                    border: `1.5px solid ${ready ? 'oklch(0.80 0.08 152)' : C.border}`,
                }}
                data-division={divisionLabel}
            >
                <div className="flex items-center justify-between px-2.5 py-2" style={{ borderBottom: `1px solid ${C.borderSoft}` }}>
                    <span
                        className="max-w-[130px] truncate text-xs"
                        style={{ color: m.team_a ? C.ink : C.muted, fontWeight: wonA ? 700 : 500 }}
                    >
                        {m.team_a ?? 'À venir'}
                    </span>
                    <span
                        className="text-sm tabular-nums"
                        style={{ fontFamily: MONO, color: wonA ? C.green : C.muted }}
                    >
                        {m.score_a ?? ''}
                    </span>
                </div>
                <div className="flex items-center justify-between px-2.5 py-2">
                    <span
                        className="max-w-[130px] truncate text-xs"
                        style={{ color: m.team_b ? C.ink : C.muted, fontWeight: wonB ? 700 : 500 }}
                    >
                        {m.team_b ?? 'À venir'}
                    </span>
                    <span
                        className="text-sm tabular-nums"
                        style={{ fontFamily: MONO, color: wonB ? C.green : C.muted }}
                    >
                        {m.score_b ?? ''}
                    </span>
                </div>
                {ready && (
                    <div className="p-1.5" style={{ borderTop: `1px solid ${C.borderSoft}` }}>
                        <button
                            onClick={() => openFinal(m)}
                            className="w-full rounded-md py-1.5 text-[11px] font-bold text-white"
                            style={{ background: C.accent }}
                        >
                            Saisir
                        </button>
                    </div>
                )}
                {finished && (
                    <div className="p-1.5" style={{ borderTop: `1px solid ${C.borderSoft}` }}>
                        <button
                            onClick={() => openFinal(m)}
                            className="w-full text-center text-[10px] uppercase"
                            style={{ color: C.muted }}
                        >
                            Corriger
                        </button>
                    </div>
                )}
            </div>
        );
    };

    // ── Standings ────────────────────────────────────────────────────────────
    const rankColor = (i: number) => (i < 2 ? C.accent : i < 4 ? C.primary : C.muted);
    const renderStandings = () => (
        <div className="flex-1 overflow-y-auto p-5">
            <div className="mx-auto flex max-w-2xl flex-col gap-3.5">
                <div
                    className="flex items-center gap-2.5 rounded-lg p-3"
                    style={{ background: C.primarySoft, border: `1px solid oklch(0.86 0.06 240)` }}
                >
                    <Trophy className="size-3.5 shrink-0" style={{ color: C.primary }} />
                    <span className="text-xs" style={{ color: C.primarySoftText }}>
                        Classement provisoire — mis à jour après chaque score saisi.
                    </span>
                </div>

                <div
                    className="overflow-hidden rounded-xl"
                    style={{ background: C.card, border: `1px solid ${C.border}` }}
                >
                    <div
                        className="grid items-center px-4 py-2.5"
                        style={{
                            gridTemplateColumns: '32px 1fr 48px 48px 56px 56px',
                            background: C.cardAlt,
                            borderBottom: `1px solid ${C.border}`,
                        }}
                    >
                        {['#', 'Équipe', 'V', 'D', 'Pts +', 'Pts −'].map((h, i) => (
                            <span
                                key={h}
                                className="text-[11px] font-bold uppercase"
                                style={{
                                    color: C.muted,
                                    letterSpacing: '0.04em',
                                    textAlign: i >= 2 ? 'center' : 'left',
                                }}
                            >
                                {h}
                            </span>
                        ))}
                    </div>
                    {standings.map((s, i) => (
                        <div
                            key={s.seed}
                            className="grid items-center px-4 py-2.5"
                            style={{
                                gridTemplateColumns: '32px 1fr 48px 48px 56px 56px',
                                borderBottom: `1px solid ${C.borderSoft}`,
                            }}
                        >
                            <span
                                className="text-[13px] font-semibold tabular-nums"
                                style={{ fontFamily: MONO, color: rankColor(i) }}
                            >
                                {i + 1}
                            </span>
                            <div className="flex min-w-0 items-center gap-2">
                                <span
                                    className="flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold"
                                    style={{ background: C.neutralBg, color: C.muted, fontFamily: MONO }}
                                >
                                    {s.seed}
                                </span>
                                <span
                                    className="truncate text-[13px] font-semibold"
                                    style={{ color: C.ink }}
                                >
                                    {s.team}
                                </span>
                            </div>
                            <span
                                className="text-center text-sm font-semibold tabular-nums"
                                style={{ fontFamily: MONO, color: C.greenText }}
                            >
                                {s.wins}
                            </span>
                            <span
                                className="text-center text-sm tabular-nums"
                                style={{ fontFamily: MONO, color: C.muted }}
                            >
                                {s.losses}
                            </span>
                            <span
                                className="text-center text-[13px] tabular-nums"
                                style={{ fontFamily: MONO, color: C.primary }}
                            >
                                {s.points_for}
                            </span>
                            <span
                                className="text-center text-[13px] tabular-nums"
                                style={{ fontFamily: MONO, color: C.muted }}
                            >
                                {s.points_against}
                            </span>
                        </div>
                    ))}
                    {standings.length === 0 && (
                        <p className="p-6 text-center text-sm" style={{ color: C.muted }}>
                            Aucun résultat pour le moment.
                        </p>
                    )}
                </div>
            </div>
        </div>
    );

    return (
        <div
            className="flex h-[calc(100svh-5rem)] flex-col overflow-hidden"
            style={{ background: C.bg, fontFamily: BODY }}
        >
            <Head title={`Déroulé — ${tournament.name}`} />
            {header}
            {tabBar}

            <div className="flex flex-1 flex-col overflow-hidden">
                {activeRound !== null && renderRound(activeRound)}
                {activeTab === 'finals' && finalsAvailable && renderFinals()}
                {activeTab === 'standings' && renderStandings()}
            </div>

            {/* Forfeit modal */}
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
                            {maxScore}-0.
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

            {/* Finals score modal */}
            {finalScoring && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-5"
                    style={{ background: 'oklch(0 0 0 / 0.52)', backdropFilter: 'blur(3px)' }}
                    onClick={(e) => e.target === e.currentTarget && setFinalScoring(null)}
                >
                    <div
                        className="w-full max-w-sm overflow-hidden rounded-2xl"
                        style={{ background: C.card }}
                    >
                        <div className="px-6 pt-5 pb-4" style={{ borderBottom: `1px solid ${C.border}` }}>
                            <h2
                                className="text-xl font-extrabold"
                                style={{ fontFamily: DISPLAY, color: C.ink }}
                            >
                                {finalMode === 'correct' ? 'Corriger le score' : 'Saisir le score'}
                            </h2>
                            <p className="text-xs" style={{ color: C.muted }}>
                                Tableau {activeDivision} · le vainqueur atteint {maxScore} points.
                            </p>
                        </div>
                        <div className="grid grid-cols-[1fr_auto_1fr] items-start gap-3 px-6 py-6">
                            <div className="flex flex-col items-center gap-2.5">
                                <span
                                    className="line-clamp-2 text-center text-[13px] font-semibold"
                                    style={{ color: C.ink }}
                                >
                                    {finalScoring.team_a}
                                </span>
                                <ScoreStepper value={fScoreA} onChange={setFScoreA} max={maxScore} />
                            </div>
                            <div
                                className="pt-8 text-center text-xl font-bold"
                                style={{ fontFamily: DISPLAY, color: C.neutral }}
                            >
                                VS
                            </div>
                            <div className="flex flex-col items-center gap-2.5">
                                <span
                                    className="line-clamp-2 text-center text-[13px] font-semibold"
                                    style={{ color: C.ink }}
                                >
                                    {finalScoring.team_b}
                                </span>
                                <ScoreStepper value={fScoreB} onChange={setFScoreB} max={maxScore} />
                            </div>
                        </div>
                        <div className="flex gap-2.5 px-6 pb-5">
                            <button
                                onClick={() => setFinalScoring(null)}
                                className="flex-1 rounded-lg py-3 text-sm font-semibold"
                                style={{ background: C.neutralBg, color: C.ink }}
                            >
                                Annuler
                            </button>
                            <button
                                onClick={submitFinal}
                                disabled={fScoreA === fScoreB}
                                className="flex flex-[2] items-center justify-center gap-2 rounded-lg py-3 text-sm font-bold text-white disabled:opacity-40"
                                style={{ background: C.green }}
                            >
                                <Check className="size-4" />
                                {finalMode === 'correct' ? 'Corriger' : 'Valider'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Déroulé', href: '#' },
];

LiveTournament.layout = { breadcrumbs };
