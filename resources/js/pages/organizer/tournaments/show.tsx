import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    CalendarDays,
    ClipboardList,
    Copy,
    DoorClosed,
    DoorOpen,
    Download,
    LayoutGrid,
    MapPin,
    Monitor,
    Pencil,
    Play,
    QrCode,
    Target,
    Timer,
    Users,
} from 'lucide-react';
import { toast } from 'sonner';
import { BODY, C, DISPLAY, MONO } from '@/lib/petanque';
import { formatDateTime } from '@/lib/tournaments';
import type { BreadcrumbItem, Tournament } from '@/types';

type RegistrationSummary = {
    pending: number;
    confirmed: number;
    checked_in: number;
    cancelled: number;
    teams: number;
};

type Props = {
    tournament: Tournament;
    registrationQr: string;
    registrationSummary: RegistrationSummary;
};

function statusTone(status: string): { bg: string; color: string } {
    switch (status) {
        case 'running':
            return { bg: C.greenBg, color: C.greenText };
        case 'registration_open':
            return { bg: C.primarySoft, color: C.primarySoftText };
        case 'checkin':
            return { bg: C.amberBg, color: C.amberText };
        case 'archived':
            return { bg: C.neutralBg, color: C.neutralText };
        default:
            return { bg: C.neutralBg, color: C.neutralText };
    }
}

function SectionTitle({ children }: { children: React.ReactNode }) {
    return (
        <div
            className="mb-4 text-[14px] font-bold uppercase"
            style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.05em' }}
        >
            {children}
        </div>
    );
}

function InfoRow({
    icon: Icon,
    label,
    value,
}: {
    icon: typeof CalendarDays;
    label: string;
    value: string;
}) {
    return (
        <div className="flex items-start gap-2.5">
            <div
                className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg"
                style={{ background: C.primarySoft }}
            >
                <Icon className="size-4" style={{ color: C.primary }} />
            </div>
            <div>
                <div className="mb-0.5 text-[11px]" style={{ color: C.muted }}>
                    {label}
                </div>
                <div className="text-[13px] font-semibold" style={{ color: C.ink }}>
                    {value}
                </div>
            </div>
        </div>
    );
}

function ghostBtn(): React.CSSProperties {
    return {
        border: `1.5px solid ${C.border}`,
        background: C.card,
        color: C.ink2,
    };
}

export default function ShowTournament({
    tournament,
    registrationQr,
    registrationSummary,
}: Props) {
    const showUrl = `/organizer/tournaments/${tournament.id}`;
    const registrationOpen = tournament.status === 'registration_open';
    const tone = statusTone(tournament.status);
    // Le format n'est figé qu'au tirage ; avant, on ne montre pas de valeurs.
    const formatDefined = tournament.current_phase !== null;

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(tournament.registration_url);
            toast.success('Lien d’inscription copié.');
        } catch {
            toast.error('Impossible de copier le lien.');
        }
    };

    const stats = [
        { label: 'En attente', value: registrationSummary.pending, color: C.ink },
        { label: 'Confirmées', value: registrationSummary.confirmed, color: C.primary },
        { label: 'Présents', value: registrationSummary.checked_in, color: C.greenText },
        { label: 'Annulées', value: registrationSummary.cancelled, color: C.muted },
        { label: 'Équipes', value: registrationSummary.teams, color: C.ink },
    ];

    return (
        <div
            className="flex h-full flex-1 flex-col overflow-hidden"
            style={{ background: C.bg, fontFamily: BODY }}
        >
            <Head title={tournament.name} />

            {/* Header */}
            <header
                className="shrink-0 px-6 py-3"
                style={{ background: C.card, borderBottom: `1px solid ${C.border}` }}
            >
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <h1
                            className="text-[22px] font-extrabold"
                            style={{ fontFamily: DISPLAY, color: C.ink }}
                        >
                            {tournament.name}
                        </h1>
                        <span
                            className="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase"
                            style={{ background: tone.bg, color: tone.color, letterSpacing: '0.04em' }}
                        >
                            {tournament.status_label}
                        </span>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={() =>
                                router.patch(
                                    `${showUrl}/${tournament.is_archived ? 'unarchive' : 'archive'}`,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                            className="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold"
                            style={ghostBtn()}
                        >
                            {tournament.is_archived ? (
                                <ArchiveRestore className="size-3.5" />
                            ) : (
                                <Archive className="size-3.5" />
                            )}
                            {tournament.is_archived ? 'Restaurer' : 'Archiver'}
                        </button>
                        <Link
                            href={`${showUrl}/edit`}
                            className="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold"
                            style={ghostBtn()}
                        >
                            <Pencil className="size-3.5" />
                            Gérer
                        </Link>
                        <Link
                            href={`${showUrl}/live`}
                            className="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-bold text-white"
                            style={{ background: C.primary }}
                        >
                            <Play className="size-3.5" />
                            Piloter
                        </Link>
                    </div>
                </div>
            </header>

            <div className="flex flex-1 flex-col gap-4 overflow-y-auto p-6">
                {/* Details + QR */}
                <div className="grid gap-4 lg:grid-cols-[1fr_260px]">
                    <div
                        className="rounded-xl p-5"
                        style={{ background: C.card, border: `1px solid ${C.border}` }}
                    >
                        <SectionTitle>Détails du concours</SectionTitle>
                        <div className="grid gap-3.5 sm:grid-cols-2">
                            <InfoRow
                                icon={CalendarDays}
                                label="Date & heure"
                                value={formatDateTime(tournament.scheduled_at)}
                            />
                            <InfoRow
                                icon={MapPin}
                                label="Lieu"
                                value={tournament.location ?? 'À définir'}
                            />
                            <InfoRow icon={Users} label="Format" value={tournament.team_format_label} />
                            <InfoRow
                                icon={Timer}
                                label="Qualifications"
                                value={
                                    formatDefined
                                        ? `${tournament.qualifying_rounds} parties · ${tournament.points_target} points`
                                        : 'Défini au tirage'
                                }
                            />
                            <InfoRow
                                icon={LayoutGrid}
                                label="Tableaux finaux"
                                value={
                                    formatDefined
                                        ? `${tournament.tableaux_count} tableau(x)`
                                        : 'Défini au tirage'
                                }
                            />
                            <InfoRow
                                icon={Target}
                                label="Équipes max."
                                value={
                                    tournament.max_teams === null
                                        ? 'Illimité'
                                        : `${tournament.max_teams} équipes`
                                }
                            />
                        </div>
                    </div>

                    <div
                        className="flex flex-col rounded-xl p-5"
                        style={{ background: C.card, border: `1px solid ${C.border}` }}
                    >
                        <div
                            className="mb-1 flex items-center gap-1.5 text-[14px] font-bold uppercase"
                            style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.05em' }}
                        >
                            <QrCode className="size-3.5" />
                            QR inscription
                        </div>
                        <p className="mb-3 text-[11px]" style={{ color: C.muted }}>
                            Affichez sur place, les joueurs scannent pour s’inscrire.
                        </p>
                        <div
                            className="mb-3 flex justify-center rounded-lg bg-white p-3"
                            style={{ border: `1px solid ${C.border}` }}
                        >
                            <img src={registrationQr} alt="QR inscription" className="size-24" />
                        </div>
                        <div
                            className="mb-2.5 rounded-md p-2"
                            style={{ background: C.bg, border: `1px solid ${C.border}` }}
                        >
                            <div className="text-[10px]" style={{ color: C.muted }}>
                                Lien d’inscription
                            </div>
                            <div
                                className="truncate text-[10px]"
                                style={{ fontFamily: MONO, color: C.ink2 }}
                            >
                                {tournament.registration_url}
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button
                                onClick={copy}
                                className="flex-1 rounded-lg py-2 text-xs font-semibold"
                                style={ghostBtn()}
                            >
                                <Copy className="mr-1 inline size-3" />
                                Copier
                            </button>
                            <a
                                href={`${showUrl}/qr`}
                                target="_blank"
                                rel="noreferrer"
                                className="flex-1 rounded-lg py-2 text-center text-xs font-semibold"
                                style={ghostBtn()}
                            >
                                <Download className="mr-1 inline size-3" />
                                Imprimer
                            </a>
                        </div>
                        <a
                            href={`/ecran/${tournament.registration_token}`}
                            target="_blank"
                            rel="noreferrer"
                            className="mt-2 inline-flex items-center justify-center gap-1.5 text-xs font-medium"
                            style={{ color: C.primary }}
                        >
                            <Monitor className="size-3.5" />
                            Ouvrir l’écran TV
                        </a>
                    </div>
                </div>

                {/* Terrains */}
                <div
                    className="rounded-xl p-5"
                    style={{ background: C.card, border: `1px solid ${C.border}` }}
                >
                    <SectionTitle>Terrains ({tournament.courts.length})</SectionTitle>
                    {tournament.courts.length === 0 ? (
                        <p className="text-sm" style={{ color: C.muted }}>
                            Aucun terrain configuré.{' '}
                            <Link
                                href={`${showUrl}/edit`}
                                className="underline-offset-4 hover:underline"
                                style={{ color: C.primary }}
                            >
                                Ajouter des terrains
                            </Link>
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {tournament.courts.map((court) => {
                                const occupied = court.status === 'occupied';

                                return (
                                    <div
                                        key={court.id}
                                        className="flex items-center gap-2 rounded-lg px-3.5 py-2"
                                        style={{
                                            background: C.cardAlt,
                                            border: `1.5px solid ${occupied ? 'oklch(0.80 0.08 152)' : C.border}`,
                                        }}
                                    >
                                        <span
                                            className="size-2 rounded-full"
                                            style={{ background: occupied ? C.green : C.neutral }}
                                        />
                                        <span
                                            className="text-[13px] font-semibold"
                                            style={{ color: C.ink }}
                                        >
                                            {court.label}
                                        </span>
                                        <span className="h-3.5 w-px" style={{ background: C.border }} />
                                        <span
                                            className="text-[11px]"
                                            style={{ color: occupied ? C.greenText : C.muted }}
                                        >
                                            {court.status_label}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                {/* Inscriptions */}
                <div
                    className="rounded-xl p-5"
                    style={{ background: C.card, border: `1px solid ${C.border}` }}
                >
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-2.5">
                        <div>
                            <SectionTitle>Inscriptions</SectionTitle>
                            <div className="-mt-3 text-[12px]" style={{ color: C.muted }}>
                                {tournament.max_teams === null
                                    ? 'Nombre d’équipes illimité'
                                    : `Maximum ${tournament.max_teams} équipes`}
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button
                                onClick={() =>
                                    router.patch(
                                        `${showUrl}/registrations/${registrationOpen ? 'close' : 'open'}`,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                className="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold"
                                style={ghostBtn()}
                            >
                                {registrationOpen ? (
                                    <DoorClosed className="size-3.5" />
                                ) : (
                                    <DoorOpen className="size-3.5" />
                                )}
                                {registrationOpen
                                    ? 'Fermer les inscriptions'
                                    : 'Ouvrir les inscriptions'}
                            </button>
                            <Link
                                href={`${showUrl}/registrations`}
                                className="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-bold text-white"
                                style={{ background: C.primary }}
                            >
                                <ClipboardList className="size-3.5" />
                                Gérer
                            </Link>
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-5">
                        {stats.map((s) => (
                            <div
                                key={s.label}
                                className="rounded-xl p-3.5 text-center"
                                style={{ border: `1.5px solid ${C.border}` }}
                            >
                                <div
                                    className="mb-1 text-[28px] leading-none font-medium tabular-nums"
                                    style={{ fontFamily: MONO, color: s.color }}
                                >
                                    {s.value}
                                </div>
                                <div className="text-[11px] font-medium" style={{ color: C.muted }}>
                                    {s.label}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Détail', href: '#' },
];

ShowTournament.layout = { breadcrumbs };
