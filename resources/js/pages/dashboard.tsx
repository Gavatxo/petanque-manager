import { Head, Link } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { BODY, C, DISPLAY, MONO } from '@/lib/petanque';
import { dashboard } from '@/routes';

type Stats = {
    organized: number;
    teams: number;
    games: number;
    attendance: number | null;
};
type Upcoming = {
    id: number;
    name: string;
    location: string | null;
    format: string;
    day: string;
    month: string;
    teams: number;
    status: string;
    status_label: string;
};
type Recent = {
    id: number;
    name: string;
    location: string | null;
    date: string;
    teams: number;
    winner: string | null;
};

type Props = {
    stats: Stats;
    chart: { label: string; count: number }[];
    upcoming: Upcoming[];
    recent: Recent[];
};

function tone(status: string): { bg: string; color: string } {
    switch (status) {
        case 'running':
            return { bg: C.greenBg, color: C.greenText };
        case 'registration_open':
            return { bg: C.primarySoft, color: C.primarySoftText };
        case 'checkin':
            return { bg: C.amberBg, color: C.amberText };
        default:
            return { bg: C.neutralBg, color: C.neutralText };
    }
}

function Kpi({
    label,
    value,
    unit,
    bar,
}: {
    label: string;
    value: string;
    unit?: string;
    bar: string;
}) {
    return (
        <div
            className="relative overflow-hidden rounded-xl p-5"
            style={{ background: C.card, border: `1px solid ${C.border}` }}
        >
            <div className="absolute inset-x-0 top-0 h-[3px]" style={{ background: bar }} />
            <div
                className="mb-2 text-[11px] font-semibold uppercase"
                style={{ color: C.muted, letterSpacing: '0.04em' }}
            >
                {label}
            </div>
            <div
                className="text-[36px] leading-none font-medium tabular-nums"
                style={{ fontFamily: MONO, color: C.ink }}
            >
                {value}
                {unit && (
                    <span className="text-xl" style={{ color: C.muted }}>
                        {unit}
                    </span>
                )}
            </div>
        </div>
    );
}

export default function Dashboard({ stats, chart, upcoming, recent }: Props) {
    const maxBar = Math.max(1, ...chart.map((b) => b.count));

    return (
        <div
            className="flex h-full flex-1 flex-col gap-5 overflow-y-auto p-6"
            style={{ background: C.bg, fontFamily: BODY }}
        >
            <Head title="Tableau de bord" />

            <div>
                <p className="text-[11px]" style={{ color: C.muted }}>
                    Tableau de bord
                </p>
                <h1
                    className="text-2xl font-extrabold"
                    style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.01em' }}
                >
                    Vue d’ensemble
                </h1>
            </div>

            {/* KPIs */}
            <div className="grid gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
                <Kpi label="Concours organisés" value={String(stats.organized)} bar={C.primary} />
                <Kpi label="Équipes accueillies" value={String(stats.teams)} bar={C.accent} />
                <Kpi label="Parties jouées" value={String(stats.games)} bar={C.green} />
                <Kpi
                    label="Taux de présence"
                    value={stats.attendance === null ? '—' : String(stats.attendance)}
                    unit={stats.attendance === null ? undefined : '%'}
                    bar={C.amber}
                />
            </div>

            {/* Chart + upcoming */}
            <div className="grid gap-4 lg:grid-cols-[1fr_1.6fr]">
                <div
                    className="rounded-xl p-5"
                    style={{ background: C.card, border: `1px solid ${C.border}` }}
                >
                    <div
                        className="mb-3.5 text-[15px] font-bold uppercase"
                        style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.03em' }}
                    >
                        Concours / mois
                    </div>
                    <div className="flex h-20 items-end gap-2 pb-1">
                        {chart.map((bar, i) => (
                            <div key={i} className="flex flex-1 flex-col items-center gap-1">
                                <div
                                    className="w-full rounded-t"
                                    style={{
                                        height: `${Math.max(4, (bar.count / maxBar) * 72)}px`,
                                        background:
                                            i === chart.length - 1
                                                ? C.primary
                                                : 'oklch(0.88 0.04 240)',
                                    }}
                                />
                                <span
                                    className="text-[10px] tabular-nums"
                                    style={{ color: C.muted, fontFamily: MONO }}
                                >
                                    {bar.label}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                <div
                    className="rounded-xl p-5"
                    style={{ background: C.card, border: `1px solid ${C.border}` }}
                >
                    <div className="mb-3.5 flex items-center justify-between">
                        <div
                            className="text-[15px] font-bold uppercase"
                            style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.03em' }}
                        >
                            Concours à venir
                        </div>
                        <Link
                            href="/organizer/tournaments"
                            className="text-xs font-medium"
                            style={{ color: C.primary }}
                        >
                            Voir tout →
                        </Link>
                    </div>
                    {upcoming.length === 0 ? (
                        <p className="text-sm" style={{ color: C.muted }}>
                            Aucun concours à venir.
                        </p>
                    ) : (
                        <div className="flex flex-col gap-2.5">
                            {upcoming.map((t) => {
                                const st = tone(t.status);

                                return (
                                    <Link
                                        key={t.id}
                                        href={`/organizer/tournaments/${t.id}`}
                                        className="flex items-center gap-3 rounded-lg p-2.5"
                                        style={{
                                            background: C.cardAlt,
                                            border: `1px solid ${C.border}`,
                                        }}
                                    >
                                        <div
                                            className="flex size-10 shrink-0 flex-col items-center justify-center rounded-lg"
                                            style={{ background: C.primarySoft }}
                                        >
                                            <span
                                                className="text-base leading-none font-medium tabular-nums"
                                                style={{ fontFamily: MONO, color: C.primary }}
                                            >
                                                {t.day}
                                            </span>
                                            <span
                                                className="text-[9px] font-bold uppercase"
                                                style={{ color: C.primarySoftText }}
                                            >
                                                {t.month}
                                            </span>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div
                                                className="truncate text-[13px] font-semibold"
                                                style={{ color: C.ink }}
                                            >
                                                {t.name}
                                            </div>
                                            <div className="text-[11px]" style={{ color: C.muted }}>
                                                {t.location ?? 'Lieu à définir'} · {t.format}
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 flex-col items-end gap-1">
                                            <span
                                                className="rounded-full px-2 py-0.5 text-[11px] font-bold"
                                                style={{ background: st.bg, color: st.color }}
                                            >
                                                {t.status_label}
                                            </span>
                                            <span
                                                className="text-[11px] tabular-nums"
                                                style={{ color: C.muted }}
                                            >
                                                {t.teams} éq.
                                            </span>
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {/* Recent */}
            <div
                className="rounded-xl p-5"
                style={{ background: C.card, border: `1px solid ${C.border}` }}
            >
                <div
                    className="mb-3.5 text-[15px] font-bold uppercase"
                    style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.03em' }}
                >
                    Concours récents
                </div>
                {recent.length === 0 ? (
                    <p className="text-sm" style={{ color: C.muted }}>
                        Aucun concours terminé pour l’instant.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse text-[13px]">
                            <thead>
                                <tr
                                    className="text-left text-[11px] font-semibold uppercase"
                                    style={{ color: C.muted }}
                                >
                                    <th
                                        className="px-2.5 py-1.5"
                                        style={{ borderBottom: `1px solid ${C.border}` }}
                                    >
                                        Concours
                                    </th>
                                    <th
                                        className="px-2.5 py-1.5"
                                        style={{ borderBottom: `1px solid ${C.border}` }}
                                    >
                                        Date
                                    </th>
                                    <th
                                        className="px-2.5 py-1.5 text-center"
                                        style={{ borderBottom: `1px solid ${C.border}` }}
                                    >
                                        Équipes
                                    </th>
                                    <th
                                        className="px-2.5 py-1.5"
                                        style={{ borderBottom: `1px solid ${C.border}` }}
                                    >
                                        Vainqueur
                                    </th>
                                    <th style={{ borderBottom: `1px solid ${C.border}` }} />
                                </tr>
                            </thead>
                            <tbody>
                                {recent.map((t) => (
                                    <tr key={t.id}>
                                        <td
                                            className="p-2.5"
                                            style={{ borderBottom: `1px solid ${C.borderSoft}` }}
                                        >
                                            <div className="font-semibold" style={{ color: C.ink }}>
                                                {t.name}
                                            </div>
                                            <div className="text-[11px]" style={{ color: C.muted }}>
                                                {t.location ?? '—'}
                                            </div>
                                        </td>
                                        <td
                                            className="p-2.5 tabular-nums"
                                            style={{
                                                borderBottom: `1px solid ${C.borderSoft}`,
                                                color: C.muted,
                                            }}
                                        >
                                            {t.date}
                                        </td>
                                        <td
                                            className="p-2.5 text-center tabular-nums"
                                            style={{
                                                borderBottom: `1px solid ${C.borderSoft}`,
                                                color: C.ink2,
                                                fontFamily: MONO,
                                            }}
                                        >
                                            {t.teams}
                                        </td>
                                        <td
                                            className="p-2.5"
                                            style={{ borderBottom: `1px solid ${C.borderSoft}` }}
                                        >
                                            <div className="flex items-center gap-1.5">
                                                <Star
                                                    className="size-3 shrink-0"
                                                    style={{ color: C.amber, fill: C.amber }}
                                                />
                                                <span style={{ color: C.ink }}>
                                                    {t.winner ?? '—'}
                                                </span>
                                            </div>
                                        </td>
                                        <td
                                            className="p-2.5 text-right"
                                            style={{ borderBottom: `1px solid ${C.borderSoft}` }}
                                        >
                                            <Link
                                                href={`/organizer/tournaments/${t.id}`}
                                                className="text-xs font-medium"
                                                style={{ color: C.primary }}
                                            >
                                                Voir →
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Tableau de bord', href: dashboard() }],
};
