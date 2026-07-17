import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    CalendarDays,
    ClipboardList,
    Copy,
    Download,
    DoorClosed,
    DoorOpen,
    LayoutGrid,
    MapPin,
    Pencil,
    QrCode,
    Target,
    Users,
} from 'lucide-react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatDateTime, statusBadgeVariant } from '@/lib/tournaments';
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
        <div className="flex items-start gap-3">
            <Icon className="text-muted-foreground mt-0.5 size-4 shrink-0" />
            <div>
                <p className="text-muted-foreground text-xs">{label}</p>
                <p className="text-sm font-medium">{value}</p>
            </div>
        </div>
    );
}

export default function ShowTournament({
    tournament,
    registrationQr,
    registrationSummary,
}: Props) {
    const showUrl = `/organizer/tournaments/${tournament.id}`;
    const registrationOpen = tournament.status === 'registration_open';

    const copyRegistrationUrl = async () => {
        try {
            await navigator.clipboard.writeText(tournament.registration_url);
            toast.success('Lien d’inscription copié.');
        } catch {
            toast.error('Impossible de copier le lien.');
        }
    };

    return (
        <>
            <Head title={tournament.name} />

            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="space-y-1">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {tournament.name}
                            </h1>
                            <Badge variant={statusBadgeVariant(tournament.status)}>
                                {tournament.status_label}
                            </Badge>
                        </div>
                        {tournament.description && (
                            <p className="text-muted-foreground max-w-prose text-sm">
                                {tournament.description}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        {tournament.is_archived ? (
                            <Button
                                variant="secondary"
                                onClick={() =>
                                    router.patch(`${showUrl}/unarchive`, {}, { preserveScroll: true })
                                }
                            >
                                <ArchiveRestore />
                                Restaurer
                            </Button>
                        ) : (
                            <Button
                                variant="secondary"
                                onClick={() =>
                                    router.patch(`${showUrl}/archive`, {}, { preserveScroll: true })
                                }
                            >
                                <Archive />
                                Archiver
                            </Button>
                        )}
                        <Button asChild>
                            <Link href={`${showUrl}/edit`}>
                                <Pencil />
                                Gérer
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Détails</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
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
                            <InfoRow
                                icon={Users}
                                label="Format d’équipe"
                                value={tournament.team_format_label}
                            />
                            <InfoRow
                                icon={Target}
                                label="Qualifications"
                                value={`${tournament.qualifying_rounds} parties · ${tournament.points_target} points`}
                            />
                            <InfoRow
                                icon={LayoutGrid}
                                label="Tableaux"
                                value={`${tournament.tableaux_count} tableau(x)`}
                            />
                            <InfoRow
                                icon={Users}
                                label="Équipes max."
                                value={
                                    tournament.max_teams === null
                                        ? 'Illimité'
                                        : String(tournament.max_teams)
                                }
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <QrCode className="size-4" />
                                QR code d’inscription
                            </CardTitle>
                            <CardDescription>
                                À afficher sur place : les joueurs scannent pour s’inscrire.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-center rounded-lg bg-white p-3">
                                <img
                                    src={registrationQr}
                                    alt="QR code d’inscription"
                                    className="size-44"
                                />
                            </div>
                            <div className="bg-muted rounded-md p-2">
                                <p className="text-muted-foreground text-xs">Lien d’inscription</p>
                                <p className="mt-1 truncate font-mono text-xs">
                                    {tournament.registration_url}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex-1"
                                    onClick={copyRegistrationUrl}
                                >
                                    <Copy />
                                    Copier
                                </Button>
                                <Button asChild variant="outline" size="sm" className="flex-1">
                                    <a href={`${showUrl}/qr`} target="_blank" rel="noreferrer">
                                        <Download />
                                        Imprimer
                                    </a>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <LayoutGrid className="size-4" />
                            Terrains ({tournament.courts.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {tournament.courts.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Aucun terrain configuré.{' '}
                                <Link
                                    href={`${showUrl}/edit`}
                                    className="text-primary underline-offset-4 hover:underline"
                                >
                                    Ajouter des terrains
                                </Link>
                            </p>
                        ) : (
                            <div className="flex flex-wrap gap-2">
                                {tournament.courts.map((court) => (
                                    <div
                                        key={court.id}
                                        className="border-border flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm"
                                    >
                                        <span className="font-medium">{court.label}</span>
                                        <Separator orientation="vertical" className="h-4" />
                                        <span className="text-muted-foreground text-xs">
                                            {court.status_label}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <Users className="size-4" />
                                    Inscriptions
                                </CardTitle>
                                <CardDescription>
                                    {tournament.max_teams === null
                                        ? 'Nombre d’équipes illimité.'
                                        : `Maximum ${tournament.max_teams} équipes.`}
                                </CardDescription>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        router.patch(
                                            `${showUrl}/registrations/${registrationOpen ? 'close' : 'open'}`,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {registrationOpen ? <DoorClosed /> : <DoorOpen />}
                                    {registrationOpen ? 'Fermer' : 'Ouvrir'}
                                </Button>
                                <Button asChild size="sm">
                                    <Link href={`${showUrl}/registrations`}>
                                        <ClipboardList />
                                        Gérer
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                            {(
                                [
                                    ['En attente', registrationSummary.pending],
                                    ['Confirmées', registrationSummary.confirmed],
                                    ['Présents', registrationSummary.checked_in],
                                    ['Annulées', registrationSummary.cancelled],
                                    ['Équipes', registrationSummary.teams],
                                ] as const
                            ).map(([label, value]) => (
                                <div
                                    key={label}
                                    className="border-border rounded-lg border p-3 text-center"
                                >
                                    <p className="text-2xl font-semibold tabular-nums">{value}</p>
                                    <p className="text-muted-foreground text-xs">{label}</p>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Détail', href: '#' },
];

ShowTournament.layout = { breadcrumbs };
