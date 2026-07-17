import { Head } from '@inertiajs/react';
import { TournamentForm } from '@/components/organizer/tournament-form';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { BreadcrumbItem, SelectOption } from '@/types';

type Props = {
    formats: SelectOption[];
    defaults: {
        team_format: string;
        qualifying_rounds: number;
        tableaux_count: number;
        points_target: number;
    };
};

export default function CreateTournament({ formats, defaults }: Props) {
    return (
        <>
            <Head title="Nouveau concours" />

            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Nouveau concours</h1>
                    <p className="text-muted-foreground text-sm">
                        Renseignez les informations et le format du concours.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Informations</CardTitle>
                        <CardDescription>
                            Vous pourrez ajouter les terrains juste après la création.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <TournamentForm
                            action="/organizer/tournaments"
                            method="post"
                            submitLabel="Créer le concours"
                            cancelUrl="/organizer/tournaments"
                            formats={formats}
                            values={{
                                name: '',
                                description: '',
                                location: '',
                                scheduled_at: '',
                                team_format: defaults.team_format,
                                qualifying_rounds: defaults.qualifying_rounds,
                                tableaux_count: defaults.tableaux_count,
                                points_target: defaults.points_target,
                                max_teams: '',
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Nouveau', href: '/organizer/tournaments/create' },
];

CreateTournament.layout = { breadcrumbs };
