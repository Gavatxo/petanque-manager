import { Form, Head } from '@inertiajs/react';
import { CalendarDays, MapPin, Users } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    tournament: {
        name: string;
        location: string | null;
        scheduled_at: string | null;
        team_format_label: string;
    };
    teamSize: number;
    registrationOpen: boolean;
    isFull: boolean;
    registeredCount: number;
    submitUrl: string;
};

function formatDate(iso: string | null): string {
    if (!iso) {
        return 'Date à définir';
    }

    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(iso));
}

export default function PublicRegister({
    tournament,
    teamSize,
    registrationOpen,
    isFull,
    registeredCount,
    submitUrl,
}: Props) {
    const canRegister = registrationOpen && !isFull;

    return (
        <div className="bg-muted/40 flex min-h-screen flex-col items-center px-4 py-10">
            <Head title={`Inscription — ${tournament.name}`} />

            <div className="w-full max-w-lg space-y-6">
                <header className="space-y-3 text-center">
                    <div className="bg-primary/10 text-primary mx-auto flex size-12 items-center justify-center rounded-full">
                        <Users className="size-6" />
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight">{tournament.name}</h1>
                    <div className="text-muted-foreground flex flex-wrap justify-center gap-x-4 gap-y-1 text-sm">
                        <span className="flex items-center gap-1.5">
                            <CalendarDays className="size-4" />
                            {formatDate(tournament.scheduled_at)}
                        </span>
                        <span className="flex items-center gap-1.5">
                            <MapPin className="size-4" />
                            {tournament.location ?? 'Lieu à définir'}
                        </span>
                    </div>
                    <p className="text-muted-foreground text-sm">
                        {tournament.team_format_label} · {registeredCount} équipe(s) inscrite(s)
                    </p>
                </header>

                <div className="bg-card text-card-foreground rounded-xl border p-6 shadow-sm">
                    {!canRegister ? (
                        <div className="space-y-2 py-6 text-center">
                            <p className="font-medium">
                                {isFull ? 'Concours complet' : 'Inscriptions fermées'}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                {isFull
                                    ? 'Le nombre maximum d’équipes est atteint.'
                                    : 'Les inscriptions ne sont pas ouvertes pour ce concours.'}
                            </p>
                        </div>
                    ) : (
                        <Form
                            action={submitUrl}
                            method="post"
                            className="space-y-6"
                            resetOnSuccess
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
                                        <Input
                                            id="team_name"
                                            name="team_name"
                                            placeholder="Les Fanny’s"
                                        />
                                        <InputError message={errors.team_name} />
                                    </div>

                                    <div className="space-y-4">
                                        {Array.from({ length: teamSize }).map((_, index) => (
                                            <fieldset
                                                key={index}
                                                className="border-border grid gap-3 rounded-lg border p-4"
                                            >
                                                <legend className="text-muted-foreground px-1 text-xs font-medium tracking-wide uppercase">
                                                    {index === 0
                                                        ? 'Joueur 1 (capitaine)'
                                                        : `Joueur ${index + 1}`}
                                                </legend>
                                                <div className="grid gap-3 sm:grid-cols-2">
                                                    <div className="grid gap-1.5">
                                                        <Label
                                                            htmlFor={`players_${index}_first`}
                                                        >
                                                            Prénom
                                                        </Label>
                                                        <Input
                                                            id={`players_${index}_first`}
                                                            name={`players[${index}][first_name]`}
                                                            required
                                                        />
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `players.${index}.first_name`
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                    <div className="grid gap-1.5">
                                                        <Label htmlFor={`players_${index}_last`}>
                                                            Nom
                                                        </Label>
                                                        <Input
                                                            id={`players_${index}_last`}
                                                            name={`players[${index}][last_name]`}
                                                            required
                                                        />
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `players.${index}.last_name`
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <div className="grid gap-1.5">
                                                    <Label htmlFor={`players_${index}_phone`}>
                                                        Téléphone{' '}
                                                        <span className="text-muted-foreground font-normal">
                                                            (facultatif)
                                                        </span>
                                                    </Label>
                                                    <Input
                                                        id={`players_${index}_phone`}
                                                        name={`players[${index}][phone]`}
                                                        type="tel"
                                                    />
                                                </div>
                                            </fieldset>
                                        ))}
                                    </div>

                                    <InputError message={errors.players} />

                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={processing}
                                    >
                                        Envoyer ma demande d’inscription
                                    </Button>
                                    <p className="text-muted-foreground text-center text-xs">
                                        Votre demande sera validée par l’organisateur.
                                    </p>
                                </>
                            )}
                        </Form>
                    )}
                </div>
            </div>
        </div>
    );
}
