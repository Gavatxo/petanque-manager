import { Head, Link, router } from '@inertiajs/react';
import { Eye, Trash2 } from 'lucide-react';
import { CourtsManager } from '@/components/organizer/courts-manager';
import { TournamentForm } from '@/components/organizer/tournament-form';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { toDateTimeLocalValue } from '@/lib/tournaments';
import type { BreadcrumbItem, SelectOption, Tournament } from '@/types';

type Props = {
    tournament: Tournament;
    formats: SelectOption[];
    statuses: SelectOption[];
};

export default function EditTournament({ tournament, formats, statuses }: Props) {
    const showUrl = `/organizer/tournaments/${tournament.id}`;

    return (
        <>
            <Head title={`Gérer — ${tournament.name}`} />

            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {tournament.name}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Modifiez le concours et gérez ses terrains.
                        </p>
                    </div>
                    <Button asChild variant="secondary">
                        <Link href={showUrl}>
                            <Eye />
                            Aperçu
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Informations &amp; format</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <TournamentForm
                            action={showUrl}
                            method="put"
                            submitLabel="Enregistrer"
                            cancelUrl={showUrl}
                            formats={formats}
                            statuses={statuses}
                            values={{
                                name: tournament.name,
                                description: tournament.description ?? '',
                                location: tournament.location ?? '',
                                scheduled_at: toDateTimeLocalValue(tournament.scheduled_at),
                                team_format: tournament.team_format,
                                qualifying_rounds: tournament.qualifying_rounds,
                                tableaux_count: tournament.tableaux_count,
                                points_target: tournament.points_target,
                                max_teams:
                                    tournament.max_teams === null
                                        ? ''
                                        : String(tournament.max_teams),
                                status: tournament.status,
                            }}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Terrains</CardTitle>
                        <CardDescription>
                            Les terrains seront attribués automatiquement pendant le concours.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <CourtsManager
                            tournamentId={tournament.id}
                            courts={tournament.courts}
                        />
                    </CardContent>
                </Card>

                <Card className="border-destructive/40">
                    <CardHeader>
                        <CardTitle className="text-destructive">Zone de danger</CardTitle>
                        <CardDescription>
                            La suppression est définitive et retire aussi les terrains associés.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="destructive">
                                    <Trash2 />
                                    Supprimer le concours
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Supprimer ce concours ?</DialogTitle>
                                    <DialogDescription>
                                        Cette action est irréversible. Le concours «{' '}
                                        {tournament.name} » et ses terrains seront supprimés.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button variant="ghost">Annuler</Button>
                                    </DialogClose>
                                    <Button
                                        variant="destructive"
                                        onClick={() => router.delete(showUrl)}
                                    >
                                        Supprimer définitivement
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Gérer', href: '#' },
];

EditTournament.layout = { breadcrumbs };
