import { Form, Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    CheckCheck,
    Crown,
    DoorClosed,
    DoorOpen,
    UserCheck,
    UserPlus,
    UsersRound,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTournamentEcho } from '@/hooks/use-tournament-echo';
import type { BreadcrumbItem } from '@/types';

type RegistrationStatus = 'pending' | 'confirmed' | 'checked_in' | 'cancelled';

type Registration = {
    id: number;
    team_name: string;
    status: RegistrationStatus;
    status_label: string;
    has_team: boolean;
    players: { name: string; is_captain: boolean }[];
};

type Props = {
    tournament: {
        id: number;
        name: string;
        status: string;
        status_label: string;
        registration_url: string;
        max_teams: number | null;
    };
    registrationOpen: boolean;
    teamSize: number;
    registrationQr: string;
    registrations: Registration[];
    teamsCount: number;
    readyToConvert: number;
};

function AddTeamDialog({
    tournamentId,
    teamSize,
}: {
    tournamentId: number;
    teamSize: number;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <Button variant="secondary" onClick={() => setOpen(true)}>
                <UserPlus />
                Ajouter une équipe
            </Button>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Ajouter une équipe</DialogTitle>
                    <DialogDescription>
                        Inscription saisie manuellement (elle sera directement confirmée).
                    </DialogDescription>
                </DialogHeader>
                <Form
                    action={`/organizer/tournaments/${tournamentId}/registrations`}
                    method="post"
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnSuccess
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="team_name">
                                    Nom de l’équipe{' '}
                                    <span className="text-muted-foreground font-normal">
                                        (facultatif)
                                    </span>
                                </Label>
                                <Input id="team_name" name="team_name" placeholder="Les Fanny’s" />
                                <InputError message={errors.team_name} />
                            </div>

                            {Array.from({ length: teamSize }).map((_, index) => (
                                <div key={index} className="grid grid-cols-2 gap-2">
                                    <div className="grid gap-1.5">
                                        <Label htmlFor={`p_${index}_first`}>
                                            Prénom {index === 0 ? '(capitaine)' : index + 1}
                                        </Label>
                                        <Input
                                            id={`p_${index}_first`}
                                            name={`players[${index}][first_name]`}
                                            required
                                        />
                                        <InputError message={errors[`players.${index}.first_name`]} />
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor={`p_${index}_last`}>Nom</Label>
                                        <Input
                                            id={`p_${index}_last`}
                                            name={`players[${index}][last_name]`}
                                            required
                                        />
                                        <InputError message={errors[`players.${index}.last_name`]} />
                                    </div>
                                </div>
                            ))}

                            <InputError message={errors.players} />

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => setOpen(false)}
                                >
                                    Annuler
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Ajouter
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

const STATUS_ORDER: { key: RegistrationStatus; label: string }[] = [
    { key: 'pending', label: 'En attente' },
    { key: 'confirmed', label: 'Confirmées' },
    { key: 'checked_in', label: 'Présence validée' },
    { key: 'cancelled', label: 'Annulées' },
];

function statusVariant(status: RegistrationStatus): 'default' | 'secondary' | 'outline' {
    switch (status) {
        case 'checked_in':
            return 'default';
        case 'cancelled':
            return 'outline';
        default:
            return 'secondary';
    }
}

function RegistrationRow({ registration }: { registration: Registration }) {
    const patch = (action: string) =>
        router.patch(`/organizer/registrations/${registration.id}/${action}`, {}, { preserveScroll: true });

    return (
        <li className="flex flex-wrap items-center justify-between gap-3 py-3">
            <div className="min-w-0">
                <p className="font-medium">{registration.team_name}</p>
                <p className="text-muted-foreground truncate text-sm">
                    {registration.players.map((player, index) => (
                        <span key={index}>
                            {index > 0 && ' · '}
                            {player.is_captain && (
                                <Crown className="mr-0.5 inline size-3 text-amber-500" />
                            )}
                            {player.name}
                        </span>
                    ))}
                </p>
            </div>

            <div className="flex shrink-0 items-center gap-2">
                {registration.has_team && (
                    <Badge variant="outline" className="gap-1">
                        <UsersRound className="size-3" />
                        Équipe créée
                    </Badge>
                )}

                {registration.status === 'pending' && (
                    <Button size="sm" variant="secondary" onClick={() => patch('confirm')}>
                        <CheckCheck />
                        Confirmer
                    </Button>
                )}

                {(registration.status === 'pending' || registration.status === 'confirmed') && (
                    <>
                        <Button size="sm" onClick={() => patch('check-in')}>
                            <UserCheck />
                            Valider présence
                        </Button>
                        <Button
                            size="icon"
                            variant="ghost"
                            title="Annuler"
                            onClick={() => patch('cancel')}
                        >
                            <Ban className="text-destructive" />
                        </Button>
                    </>
                )}
            </div>
        </li>
    );
}

export default function TournamentRegistrations({
    tournament,
    registrationOpen,
    teamSize,
    registrationQr,
    registrations,
    teamsCount,
    readyToConvert,
}: Props) {
    const showUrl = `/organizer/tournaments/${tournament.id}`;
    useTournamentEcho(tournament.id);

    const toggleRegistrations = () =>
        router.patch(
            `${showUrl}/registrations/${registrationOpen ? 'close' : 'open'}`,
            {},
            { preserveScroll: true },
        );

    const createTeams = () =>
        router.post(`${showUrl}/registrations/create-teams`, {}, { preserveScroll: true });

    return (
        <>
            <Head title={`Inscriptions — ${tournament.name}`} />

            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <Button asChild variant="ghost" size="sm" className="mb-1 -ml-2">
                            <Link href={showUrl}>
                                <ArrowLeft />
                                {tournament.name}
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-semibold tracking-tight">Inscriptions</h1>
                        <p className="text-muted-foreground text-sm">
                            Statut du concours : {tournament.status_label}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <AddTeamDialog tournamentId={tournament.id} teamSize={teamSize} />
                        <Button
                            variant={registrationOpen ? 'outline' : 'default'}
                            onClick={toggleRegistrations}
                        >
                            {registrationOpen ? <DoorClosed /> : <DoorOpen />}
                            {registrationOpen
                                ? 'Fermer les inscriptions'
                                : 'Ouvrir les inscriptions'}
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Créer les équipes officielles</CardTitle>
                            <CardDescription>
                                Les inscriptions dont la présence est validée deviennent des équipes
                                officielles, prêtes pour le concours.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap items-center gap-4">
                            <div className="flex items-center gap-6 text-sm">
                                <div>
                                    <p className="text-2xl font-semibold tabular-nums">
                                        {readyToConvert}
                                    </p>
                                    <p className="text-muted-foreground">présences à convertir</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-semibold tabular-nums">
                                        {teamsCount}
                                    </p>
                                    <p className="text-muted-foreground">équipes officielles</p>
                                </div>
                            </div>
                            <Button
                                className="ml-auto"
                                disabled={readyToConvert === 0}
                                onClick={createTeams}
                            >
                                <UsersRound />
                                Créer {readyToConvert > 0 ? readyToConvert : ''} équipe(s)
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">QR d’inscription</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex justify-center rounded-lg bg-white p-2">
                                <img
                                    src={registrationQr}
                                    alt="QR code d’inscription"
                                    className="size-32"
                                />
                            </div>
                            <p className="text-muted-foreground truncate text-center font-mono text-xs">
                                {tournament.registration_url}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {registrations.length === 0 ? (
                    <Card>
                        <CardContent className="text-muted-foreground py-10 text-center text-sm">
                            Aucune demande d’inscription pour le moment.
                        </CardContent>
                    </Card>
                ) : (
                    STATUS_ORDER.map(({ key, label }) => {
                        const group = registrations.filter((r) => r.status === key);

                        if (group.length === 0) {
                            return null;
                        }

                        return (
                            <Card key={key}>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Badge variant={statusVariant(key)}>{label}</Badge>
                                        <span className="text-muted-foreground text-sm font-normal">
                                            {group.length}
                                        </span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul className="divide-border divide-y">
                                        {group.map((registration) => (
                                            <RegistrationRow
                                                key={registration.id}
                                                registration={registration}
                                            />
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        );
                    })
                )}
            </div>
        </>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Inscriptions', href: '#' },
];

TournamentRegistrations.layout = { breadcrumbs };
