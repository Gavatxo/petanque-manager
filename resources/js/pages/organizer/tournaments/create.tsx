import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, Info } from 'lucide-react';
import { BODY, C, DISPLAY } from '@/lib/petanque';
import type { BreadcrumbItem, SelectOption } from '@/types';

type Props = {
    formats: SelectOption[];
    defaults: {
        team_format: string;
    };
};

type FormData = {
    name: string;
    description: string;
    location: string;
    scheduled_at: string;
    team_format: string;
    max_teams: string;
};

// Métadonnées d'affichage des cartes « type d'équipe » (l'ordre et les libellés
// viennent du back-end ; l'emoji et le sous-titre sont purement visuels).
const FORMAT_META: Record<string, { emoji: string; players: string }> = {
    tete_a_tete: { emoji: '🧍', players: '1 joueur / éq.' },
    doublette: { emoji: '👥', players: '2 joueurs / éq.' },
    triplette: { emoji: '👨‍👩‍👦', players: '3 joueurs / éq.' },
};

const STEPS = [
    'Créer',
    'Terrains & inscriptions',
    'Valider équipes',
    'Tirage & départ',
] as const;

function Stepper() {
    return (
        <div className="flex items-center">
            {STEPS.map((label, i) => {
                const active = i === 0;

                return (
                    <div key={label} className="flex flex-1 items-center last:flex-none">
                        <div className="flex items-center gap-2">
                            <span
                                className="flex size-[26px] shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                style={
                                    active
                                        ? { background: C.primary, color: '#fff' }
                                        : {
                                              border: `1.5px solid ${C.border}`,
                                              color: C.muted,
                                          }
                                }
                            >
                                {i + 1}
                            </span>
                            <span
                                className="text-[13px] whitespace-nowrap"
                                style={{
                                    color: active ? C.primary : C.muted,
                                    fontWeight: active ? 700 : 400,
                                }}
                            >
                                {label}
                            </span>
                        </div>
                        {i < STEPS.length - 1 && (
                            <div
                                className="mx-3 hidden h-px flex-1 sm:block"
                                style={{ background: C.border }}
                            />
                        )}
                    </div>
                );
            })}
        </div>
    );
}

function SectionCard({
    title,
    subtitle,
    children,
}: {
    title: string;
    subtitle?: string;
    children: React.ReactNode;
}) {
    return (
        <div
            className="overflow-hidden rounded-xl"
            style={{ background: C.card, border: `1px solid ${C.border}` }}
        >
            <div className="px-5 py-4" style={{ borderBottom: `1px solid ${C.borderSoft}` }}>
                <h2
                    className="text-[16px] font-bold uppercase"
                    style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.03em' }}
                >
                    {title}
                </h2>
                {subtitle && (
                    <p className="mt-0.5 text-[12px]" style={{ color: C.muted }}>
                        {subtitle}
                    </p>
                )}
            </div>
            <div className="flex flex-col gap-4 p-5">{children}</div>
        </div>
    );
}

function FieldLabel({
    htmlFor,
    children,
    required,
    hint,
}: {
    htmlFor: string;
    children: React.ReactNode;
    required?: boolean;
    hint?: string;
}) {
    return (
        <label
            htmlFor={htmlFor}
            className="mb-1.5 block text-[13px] font-semibold"
            style={{ color: C.ink2 }}
        >
            {children}
            {required && <span style={{ color: C.accent }}> *</span>}
            {hint && (
                <span className="font-normal" style={{ color: C.muted }}>
                    {' '}
                    {hint}
                </span>
            )}
        </label>
    );
}

const inputStyle: React.CSSProperties = {
    width: '100%',
    padding: '11px 14px',
    border: `1.5px solid ${C.border}`,
    borderRadius: 8,
    fontSize: 14,
    fontFamily: BODY,
    background: C.card,
    color: C.ink,
    boxSizing: 'border-box',
};

function ErrorText({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p className="mt-1 text-[12px]" style={{ color: C.accentHover }}>
            {message}
        </p>
    );
}

export default function CreateTournament({ formats, defaults }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        description: '',
        location: '',
        scheduled_at: '',
        team_format: defaults.team_format,
        max_teams: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/organizer/tournaments');
    };

    return (
        <div className="flex-1 overflow-y-auto" style={{ background: C.bg, fontFamily: BODY }}>
            <Head title="Nouveau concours" />

            <form onSubmit={submit} className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6">
                <div>
                    <h1
                        className="text-[22px] font-extrabold"
                        style={{ fontFamily: DISPLAY, color: C.ink }}
                    >
                        Nouveau concours
                    </h1>
                    <p className="text-sm" style={{ color: C.muted }}>
                        Créez le concours, puis ajoutez terrains et équipes.
                    </p>
                </div>

                <Stepper />

                <SectionCard title="Informations">
                    <div>
                        <FieldLabel htmlFor="name" required>
                            Nom du concours
                        </FieldLabel>
                        <input
                            id="name"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            autoFocus
                            placeholder="Ex. Grand Prix de la Boule d'Or"
                            style={inputStyle}
                        />
                        <ErrorText message={errors.name} />
                    </div>

                    <div>
                        <FieldLabel htmlFor="description" hint="(facultatif)">
                            Description
                        </FieldLabel>
                        <textarea
                            id="description"
                            name="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={2}
                            placeholder="Informations complémentaires, règlement particulier…"
                            style={{ ...inputStyle, minHeight: 64, resize: 'vertical' }}
                        />
                        <ErrorText message={errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <FieldLabel htmlFor="location">Lieu</FieldLabel>
                            <input
                                id="location"
                                name="location"
                                value={data.location}
                                onChange={(e) => setData('location', e.target.value)}
                                placeholder="Boulodrome, ville…"
                                style={inputStyle}
                            />
                            <ErrorText message={errors.location} />
                        </div>
                        <div>
                            <FieldLabel htmlFor="scheduled_at">Date &amp; heure</FieldLabel>
                            <input
                                id="scheduled_at"
                                name="scheduled_at"
                                type="datetime-local"
                                value={data.scheduled_at}
                                onChange={(e) => setData('scheduled_at', e.target.value)}
                                style={inputStyle}
                            />
                            <ErrorText message={errors.scheduled_at} />
                        </div>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Format"
                    subtitle="Le type d'équipe est fixé maintenant ; le reste du format sera proposé au tirage."
                >
                    <div>
                        <FieldLabel htmlFor="team_format" required>
                            Type d'équipe
                        </FieldLabel>
                        <div className="grid grid-cols-3 gap-2.5">
                            {formats.map((option) => {
                                const selected = data.team_format === option.value;
                                const meta = FORMAT_META[option.value];

                                return (
                                    <button
                                        type="button"
                                        key={option.value}
                                        onClick={() => setData('team_format', option.value)}
                                        aria-pressed={selected}
                                        className="rounded-[10px] p-3.5 text-center transition-colors"
                                        style={{
                                            border: `2px solid ${selected ? C.primary : C.border}`,
                                            background: selected ? C.primarySoft : C.cardAlt,
                                        }}
                                    >
                                        <div className="mb-1.5 text-[22px]">
                                            {meta?.emoji ?? '🎯'}
                                        </div>
                                        <div
                                            className="text-[13px] font-bold"
                                            style={{ color: selected ? C.primarySoftText : C.ink }}
                                        >
                                            {option.label}
                                        </div>
                                        {meta && (
                                            <div
                                                className="mt-0.5 text-[11px]"
                                                style={{ color: C.muted }}
                                            >
                                                {meta.players}
                                            </div>
                                        )}
                                    </button>
                                );
                            })}
                        </div>
                        <ErrorText message={errors.team_format} />
                    </div>

                    <div className="max-w-[220px]">
                        <FieldLabel htmlFor="max_teams" hint="(facultatif)">
                            Équipes max.
                        </FieldLabel>
                        <input
                            id="max_teams"
                            name="max_teams"
                            type="number"
                            min={2}
                            max={512}
                            value={data.max_teams}
                            onChange={(e) => setData('max_teams', e.target.value)}
                            placeholder="Illimité"
                            style={inputStyle}
                        />
                        <ErrorText message={errors.max_teams} />
                    </div>

                    <div
                        className="flex gap-2.5 rounded-[9px] p-3.5"
                        style={{
                            background: C.primarySoft,
                            border: `1px solid oklch(0.86 0.06 240)`,
                        }}
                    >
                        <Info className="mt-0.5 size-4 shrink-0" style={{ color: C.primary }} />
                        <p
                            className="text-[12px] leading-relaxed"
                            style={{ color: C.primarySoftText }}
                        >
                            Le nombre de parties qualificatives, de tableaux et les points à
                            atteindre seront <strong>suggérés automatiquement</strong> lors du
                            tirage, en fonction du nombre d'équipes inscrites. Vous pourrez toujours
                            les ajuster.
                        </p>
                    </div>
                </SectionCard>

                <div className="flex items-center gap-4 pb-6">
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-bold text-white disabled:opacity-50"
                        style={{ background: C.primary }}
                    >
                        Créer le concours
                        <ArrowRight className="size-3.5" />
                    </button>
                    <Link
                        href="/organizer/tournaments"
                        className="text-sm"
                        style={{ color: C.muted }}
                    >
                        Annuler
                    </Link>
                </div>
            </form>
        </div>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Nouveau', href: '/organizer/tournaments/create' },
];

CreateTournament.layout = { breadcrumbs };
