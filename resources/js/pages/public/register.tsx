import { Form, Head } from '@inertiajs/react';
import { CalendarDays, MapPin } from 'lucide-react';
import InputError from '@/components/input-error';
import { BODY, C, DISPLAY } from '@/lib/petanque';

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

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeStyle: 'short' }).format(
        new Date(iso),
    );
}

const inputCls =
    'w-full rounded-lg border-[1.5px] border-[oklch(0.87_0.018_65)] bg-white px-3.5 py-2.5 text-sm text-[oklch(0.16_0.015_250)] outline-none placeholder:text-[oklch(0.68_0.018_65)] focus:border-[oklch(0.42_0.16_240)] focus:ring-[3px] focus:ring-[oklch(0.42_0.16_240)]/15';
const smallInputCls = inputCls.replace('px-3.5 py-2.5 text-sm', 'px-3 py-2 text-[13px]');

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
        <div className="flex min-h-screen flex-col items-center px-4 py-8" style={{ background: C.bg, fontFamily: BODY }}>
            <Head title={`Inscription — ${tournament.name}`} />

            <header className="mb-7 w-full max-w-lg text-center">
                <div
                    className="mx-auto mb-3.5 flex size-13 items-center justify-center rounded-full"
                    style={{ background: C.primarySoft, width: 52, height: 52 }}
                >
                    <span className="text-2xl" role="img" aria-label="boule">
                        🎯
                    </span>
                </div>
                <h1
                    className="mb-2.5 text-[28px] font-extrabold"
                    style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.01em' }}
                >
                    {tournament.name}
                </h1>
                <div
                    className="mb-2 flex flex-wrap justify-center gap-x-4 gap-y-1 text-[13px]"
                    style={{ color: C.muted }}
                >
                    <span className="flex items-center gap-1.5">
                        <CalendarDays className="size-3.5" />
                        {formatDate(tournament.scheduled_at)}
                    </span>
                    <span className="flex items-center gap-1.5">
                        <MapPin className="size-3.5" />
                        {tournament.location ?? 'Lieu à définir'}
                    </span>
                </div>
                <p className="text-[13px]" style={{ color: C.muted }}>
                    {tournament.team_format_label} · {registeredCount} équipe(s) inscrite(s)
                </p>
            </header>

            <div
                className="w-full max-w-lg rounded-2xl p-7"
                style={{
                    background: C.card,
                    border: `1px solid ${C.border}`,
                    boxShadow: '0 2px 12px oklch(0 0 0 / 0.04)',
                }}
            >
                {!canRegister ? (
                    <div className="space-y-2 py-6 text-center">
                        <p className="font-semibold" style={{ color: C.ink }}>
                            {isFull ? 'Concours complet' : 'Inscriptions fermées'}
                        </p>
                        <p className="text-sm" style={{ color: C.muted }}>
                            {isFull
                                ? 'Le nombre maximum d’équipes est atteint.'
                                : 'Les inscriptions ne sont pas ouvertes pour ce concours.'}
                        </p>
                    </div>
                ) : (
                    <Form action={submitUrl} method="post" resetOnSuccess className="flex flex-col gap-5">
                        {({ processing, errors }) => (
                            <>
                                <div>
                                    <label
                                        className="mb-1.5 block text-[13px] font-semibold"
                                        style={{ color: 'oklch(0.25 0.015 250)' }}
                                    >
                                        Nom de l’équipe{' '}
                                        <span className="font-normal" style={{ color: C.muted }}>
                                            (facultatif)
                                        </span>
                                    </label>
                                    <input name="team_name" placeholder="Les Fanny’s" className={inputCls} />
                                    <InputError message={errors.team_name} />
                                </div>

                                {Array.from({ length: teamSize }).map((_, index) => (
                                    <fieldset
                                        key={index}
                                        className="rounded-xl p-4"
                                        style={{ border: `1.5px solid ${C.border}` }}
                                    >
                                        <legend
                                            className="px-1.5 text-[11px] font-bold uppercase"
                                            style={{
                                                color: index === 0 ? C.primary : C.muted,
                                                letterSpacing: '0.07em',
                                            }}
                                        >
                                            {index === 0 ? 'Joueur 1 — Capitaine' : `Joueur ${index + 1}`}
                                        </legend>
                                        <div className="mb-3 grid grid-cols-2 gap-3">
                                            <div>
                                                <label
                                                    className="mb-1.5 block text-xs font-semibold"
                                                    style={{ color: 'oklch(0.30 0.015 250)' }}
                                                >
                                                    Prénom
                                                </label>
                                                <input
                                                    name={`players[${index}][first_name]`}
                                                    required
                                                    className={smallInputCls}
                                                />
                                                <InputError message={errors[`players.${index}.first_name`]} />
                                            </div>
                                            <div>
                                                <label
                                                    className="mb-1.5 block text-xs font-semibold"
                                                    style={{ color: 'oklch(0.30 0.015 250)' }}
                                                >
                                                    Nom
                                                </label>
                                                <input
                                                    name={`players[${index}][last_name]`}
                                                    required
                                                    className={smallInputCls}
                                                />
                                                <InputError message={errors[`players.${index}.last_name`]} />
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                className="mb-1.5 block text-xs font-semibold"
                                                style={{ color: 'oklch(0.30 0.015 250)' }}
                                            >
                                                Téléphone{' '}
                                                <span className="font-normal" style={{ color: C.muted }}>
                                                    (facultatif)
                                                </span>
                                            </label>
                                            <input
                                                name={`players[${index}][phone]`}
                                                type="tel"
                                                className={smallInputCls}
                                            />
                                        </div>
                                    </fieldset>
                                ))}

                                <InputError message={errors.players} />

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full rounded-lg py-3.5 text-[15px] font-bold text-white disabled:opacity-50"
                                    style={{ background: C.accent }}
                                >
                                    Envoyer ma demande d’inscription
                                </button>
                                <p className="text-center text-xs" style={{ color: C.muted }}>
                                    Votre demande sera validée par l’organisateur.
                                </p>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </div>
    );
}
