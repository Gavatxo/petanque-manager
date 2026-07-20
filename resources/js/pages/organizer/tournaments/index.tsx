import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    CalendarDays,
    LayoutGrid,
    MapPin,
    Pencil,
    Plus,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime, statusBadgeVariant } from '@/lib/tournaments';
import type { BreadcrumbItem, TournamentListItem } from '@/types';

type Props = {
    tournaments: TournamentListItem[];
};

function TournamentCard({ tournament }: { tournament: TournamentListItem }) {
    const showUrl = `/organizer/tournaments/${tournament.id}`;

    return (
        <Card className="hover:border-primary/50 relative flex flex-col transition-colors hover:shadow-md">
            <CardHeader>
                <div className="flex items-start justify-between gap-3">
                    <CardTitle className="text-base leading-tight">
                        <Link
                            href={showUrl}
                            className="hover:text-primary transition-colors after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none"
                            aria-label={`Ouvrir ${tournament.name}`}
                        >
                            {tournament.name}
                        </Link>
                    </CardTitle>
                    <Badge variant={statusBadgeVariant(tournament.status)}>
                        {tournament.status_label}
                    </Badge>
                </div>
            </CardHeader>

            <CardContent className="text-muted-foreground flex-1 space-y-2 text-sm">
                <p className="flex items-center gap-2">
                    <CalendarDays className="size-4 shrink-0" />
                    {formatDateTime(tournament.scheduled_at)}
                </p>
                <p className="flex items-center gap-2">
                    <MapPin className="size-4 shrink-0" />
                    {tournament.location ?? 'Lieu à définir'}
                </p>
                <div className="flex flex-wrap gap-x-4 gap-y-2 pt-1">
                    <span className="flex items-center gap-1.5">
                        <Users className="size-4 shrink-0" />
                        {tournament.team_format_label}
                    </span>
                    <span className="flex items-center gap-1.5">
                        <Target className="size-4 shrink-0" />
                        {tournament.current_phase === null
                            ? 'Format défini au tirage'
                            : `${tournament.qualifying_rounds} parties · ${tournament.tableaux_count} tableau(x)`}
                    </span>
                    <span className="flex items-center gap-1.5">
                        <LayoutGrid className="size-4 shrink-0" />
                        {tournament.courts_count} terrain(s)
                    </span>
                </div>
            </CardContent>

            <CardFooter className="relative z-10 gap-2">
                <Button asChild variant="secondary" size="sm">
                    <Link href={`${showUrl}/edit`}>
                        <Pencil />
                        Gérer
                    </Link>
                </Button>
                {tournament.is_archived ? (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() =>
                            router.patch(`${showUrl}/unarchive`, {}, { preserveScroll: true })
                        }
                    >
                        <ArchiveRestore />
                        Restaurer
                    </Button>
                ) : (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() =>
                            router.patch(`${showUrl}/archive`, {}, { preserveScroll: true })
                        }
                    >
                        <Archive />
                        Archiver
                    </Button>
                )}
            </CardFooter>
        </Card>
    );
}

export default function TournamentsIndex({ tournaments }: Props) {
    const active = tournaments.filter((tournament) => !tournament.is_archived);
    const archived = tournaments.filter((tournament) => tournament.is_archived);

    return (
        <>
            <Head title="Concours" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Concours</h1>
                        <p className="text-muted-foreground text-sm">
                            Créez et pilotez vos concours de pétanque.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/organizer/tournaments/create">
                            <Plus />
                            Nouveau concours
                        </Link>
                    </Button>
                </div>

                {tournaments.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-12 text-center">
                        <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                            <Trophy className="text-muted-foreground size-6" />
                        </div>
                        <div className="space-y-1">
                            <p className="font-medium">Aucun concours pour l’instant</p>
                            <p className="text-muted-foreground text-sm">
                                Lancez votre premier concours pour commencer.
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/organizer/tournaments/create">
                                <Plus />
                                Créer un concours
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-8">
                        <section className="space-y-3">
                            <h2 className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                                Actifs ({active.length})
                            </h2>
                            {active.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    Aucun concours actif.
                                </p>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {active.map((tournament) => (
                                        <TournamentCard
                                            key={tournament.id}
                                            tournament={tournament}
                                        />
                                    ))}
                                </div>
                            )}
                        </section>

                        {archived.length > 0 && (
                            <section className="space-y-3">
                                <h2 className="text-muted-foreground text-xs font-semibold tracking-wider uppercase">
                                    Archivés ({archived.length})
                                </h2>
                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                    {archived.map((tournament) => (
                                        <TournamentCard
                                            key={tournament.id}
                                            tournament={tournament}
                                        />
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Concours', href: '/organizer/tournaments' }];

TournamentsIndex.layout = { breadcrumbs };
