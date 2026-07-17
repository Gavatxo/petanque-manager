import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Plus } from 'lucide-react';
import { BODY, C, DISPLAY } from '@/lib/petanque';
import { dashboard, login, register } from '@/routes';

const DARK = 'oklch(0.16 0.02 250)';

function BoulesMark({ size = 19 }: { size?: number }) {
    const r = size / 2;

    return (
        <svg width={size} height={size} viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="9.5" cy="9.5" r={r * 0.74} fill="oklch(0.96 0.025 40)" />
            <circle cx="7" cy="8" r="1.3" fill="oklch(0.60 0.20 40)" opacity="0.65" />
            <circle cx="12" cy="7" r="1.1" fill="oklch(0.60 0.20 40)" opacity="0.65" />
            <circle cx="12.5" cy="12" r="1.3" fill="oklch(0.60 0.20 40)" opacity="0.65" />
        </svg>
    );
}

function FeatureCard({
    tint,
    icon,
    title,
    body,
    star,
    stripe,
}: {
    tint: string;
    icon: React.ReactNode;
    title: string;
    body: string;
    star?: boolean;
    stripe?: boolean;
}) {
    return (
        <div
            className="relative overflow-hidden rounded-2xl p-7"
            style={{ background: C.bg, border: `1px solid ${C.border}` }}
        >
            {stripe && <div className="absolute inset-x-0 top-0 h-[3px]" style={{ background: C.accent }} />}
            <div
                className="mb-4.5 flex items-center justify-center rounded-xl"
                style={{ width: 48, height: 48, background: tint, marginBottom: 18 }}
            >
                {icon}
            </div>
            <h3 className="mb-2.5 text-[22px] font-bold" style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.01em' }}>
                {title}
            </h3>
            <p className="text-sm" style={{ color: C.muted, lineHeight: 1.6 }}>
                {body}
            </p>
            {star && (
                <div
                    className="mt-3.5 inline-flex items-center gap-1.5 rounded-xl px-2.5 py-1 text-[11px] font-bold"
                    style={{ background: 'oklch(0.96 0.06 40)', color: 'oklch(0.55 0.20 40)' }}
                >
                    ⭐ Fonctionnalité star
                </div>
            )}
        </div>
    );
}

function Step({ n, tint, title, body }: { n: number; tint: string; title: string; body: string }) {
    return (
        <div className="px-7 text-center">
            <div
                className="relative z-10 mx-auto mb-5 flex items-center justify-center rounded-full text-2xl font-extrabold text-white"
                style={{ width: 56, height: 56, background: tint, fontFamily: DISPLAY, boxShadow: `0 0 0 6px ${C.bg}` }}
            >
                {n}
            </div>
            <h3 className="mb-2 text-[20px] font-bold" style={{ fontFamily: DISPLAY, color: C.ink }}>
                {title}
            </h3>
            <p className="text-sm" style={{ color: C.muted, lineHeight: 1.6 }}>
                {body}
            </p>
        </div>
    );
}

function Stat({ value, label, accent }: { value: string; label: string; accent?: boolean }) {
    return (
        <div>
            <div
                className="text-[44px] font-extrabold tabular-nums"
                style={{ fontFamily: DISPLAY, color: accent ? C.accent : 'white', letterSpacing: '-0.01em' }}
            >
                {value}
            </div>
            <div className="mt-0.5 text-[13px]" style={{ color: 'oklch(0.68 0.08 240)' }}>
                {label}
            </div>
        </div>
    );
}

export default function Welcome() {
    const { auth } = usePage().props;
    const primaryHref = auth.user ? dashboard() : register();
    const secondaryHref = auth.user ? dashboard() : login();

    return (
        <div style={{ background: C.bg, fontFamily: BODY }}>
            <Head title="Pétanque Manager — gérez vos concours de pétanque" />

            {/* NAV */}
            <nav
                className="sticky top-0 z-50 px-8"
                style={{ background: 'oklch(0.965 0.008 65 / 0.92)', backdropFilter: 'blur(10px)', borderBottom: `1px solid ${C.border}` }}
            >
                <div className="mx-auto flex h-15 max-w-[1120px] items-center justify-between" style={{ height: 60 }}>
                    <div className="flex items-center gap-2.5">
                        <div
                            className="flex items-center justify-center rounded-full"
                            style={{ width: 34, height: 34, background: C.accent, boxShadow: '0 2px 8px oklch(0.68 0.22 40 / 0.35)' }}
                        >
                            <BoulesMark />
                        </div>
                        <div>
                            <span className="text-[17px] font-extrabold" style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.03em' }}>
                                PÉTANQUE
                            </span>
                            <span className="ml-1 text-[13px]" style={{ color: C.muted }}>
                                Manager
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center gap-2.5">
                        <Link href={secondaryHref} className="rounded-md px-4 py-2 text-[13px] font-medium" style={{ color: 'oklch(0.35 0.015 250)' }}>
                            {auth.user ? 'Mon espace' : 'Se connecter'}
                        </Link>
                        <Link
                            href={primaryHref}
                            className="rounded-md px-4.5 py-2 text-[13px] font-bold text-white"
                            style={{ padding: '8px 18px', background: C.accent, boxShadow: '0 2px 8px oklch(0.68 0.22 40 / 0.3)' }}
                        >
                            Créer un concours
                        </Link>
                    </div>
                </div>
            </nav>

            {/* HERO */}
            <section className="relative flex items-center overflow-hidden px-8" style={{ background: DARK, minHeight: '86vh', padding: '60px 32px' }}>
                <div
                    className="pointer-events-none absolute inset-0"
                    style={{ backgroundImage: 'radial-gradient(oklch(0.42 0.16 240 / 0.12) 1px, transparent 1px)', backgroundSize: '28px 28px' }}
                />
                <div
                    className="pointer-events-none absolute inset-0"
                    style={{ background: 'radial-gradient(ellipse 70% 60% at 60% 50%, oklch(0.28 0.12 240 / 0.35) 0%, transparent 70%)' }}
                />
                <div className="relative z-10 mx-auto w-full max-w-[1120px]">
                    <div className="max-w-[640px]">
                        <div
                            className="mb-6 inline-flex items-center gap-2 rounded-full px-3 py-1.5"
                            style={{ background: 'oklch(0.42 0.16 240 / 0.15)', border: '1px solid oklch(0.42 0.16 240 / 0.3)' }}
                        >
                            <span className="size-1.5 rounded-full" style={{ background: C.green }} />
                            <span className="text-xs font-semibold uppercase" style={{ color: 'oklch(0.70 0.10 240)', letterSpacing: '0.05em' }}>
                                Gestion de concours pétanque
                            </span>
                        </div>
                        <h1
                            className="mb-5 font-extrabold text-white"
                            style={{ fontFamily: DISPLAY, fontSize: 'clamp(48px,6vw,78px)', lineHeight: 1, letterSpacing: '-0.01em', textWrap: 'balance' }}
                        >
                            VOTRE BOULODROME,
                            <br />
                            <span style={{ color: C.accent }}>CONNECTÉ.</span>
                        </h1>
                        <p className="mb-9 max-w-[520px] text-[18px]" style={{ color: 'oklch(0.72 0.04 250)', lineHeight: 1.65 }}>
                            Inscriptions par QR code, gestion du live depuis une tablette, résultats sur l’écran TV du
                            club. Tout en un, simple, gratuit pour les organisateurs.
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <Link
                                href={primaryHref}
                                className="inline-flex items-center gap-2 rounded-[9px] px-7 py-3.5 text-[15px] font-bold text-white"
                                style={{ background: C.accent, boxShadow: '0 4px 20px oklch(0.68 0.22 40 / 0.35)' }}
                            >
                                <Plus className="size-4" strokeWidth={2.5} />
                                Créer un concours
                            </Link>
                            <Link
                                href={secondaryHref}
                                className="inline-flex items-center gap-2 rounded-[9px] px-7 py-3.5 text-[15px] font-semibold"
                                style={{ color: 'oklch(0.80 0.04 250)', border: '1.5px solid oklch(0.35 0.05 250)' }}
                            >
                                {auth.user ? 'Mon espace' : 'Se connecter'}
                                <ArrowRight className="size-3.5" />
                            </Link>
                        </div>
                        <p className="mt-5 text-xs" style={{ color: 'oklch(0.45 0.04 250)' }}>
                            Conçu pour les clubs et les organisateurs de pétanque.
                        </p>
                    </div>
                </div>
            </section>

            {/* FEATURES */}
            <section className="px-8" style={{ padding: '72px 32px', background: C.card }}>
                <div className="mx-auto max-w-[1120px]">
                    <div className="mb-12 text-center">
                        <h2 className="mb-2.5 text-[38px] font-extrabold" style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.01em' }}>
                            Tout ce dont vous avez besoin
                        </h2>
                        <p className="mx-auto max-w-[480px] text-base" style={{ color: C.muted }}>
                            Une seule application, du premier joueur inscrit au podium final.
                        </p>
                    </div>
                    <div className="grid gap-6 md:grid-cols-3">
                        <FeatureCard
                            tint="oklch(0.94 0.04 240)"
                            icon={<QrIcon />}
                            title="Inscriptions QR"
                            body="Affichez un QR code sur le boulodrome. Les joueurs s’inscrivent depuis leur téléphone, sans papier et sans file."
                        />
                        <FeatureCard
                            tint="oklch(0.96 0.06 40)"
                            icon={<LiveIcon />}
                            title="Gestion live"
                            body="Tableau de bord sur tablette pour piloter les matchs en temps réel : affectation des terrains, saisie des scores au toucher."
                            stripe
                            star
                        />
                        <FeatureCard
                            tint="oklch(0.93 0.06 152)"
                            icon={<TvIcon />}
                            title="Écran TV club"
                            body="Affichez les terrains et les matchs sur la télévision du boulodrome. Les spectateurs et les joueurs suivent en direct."
                        />
                    </div>
                </div>
            </section>

            {/* HOW IT WORKS */}
            <section className="px-8" style={{ padding: '72px 32px', background: C.bg }}>
                <div className="mx-auto max-w-[1120px]">
                    <div className="mb-13 text-center" style={{ marginBottom: 52 }}>
                        <h2 className="mb-2.5 text-[38px] font-extrabold" style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.01em' }}>
                            Comment ça marche ?
                        </h2>
                        <p className="text-base" style={{ color: C.muted }}>
                            Organisez un concours en quelques minutes.
                        </p>
                    </div>
                    <div className="relative grid gap-y-10 md:grid-cols-3 md:gap-y-0">
                        <div
                            className="pointer-events-none absolute hidden md:block"
                            style={{ top: 28, left: 'calc(16.7% + 12px)', right: 'calc(16.7% + 12px)', height: 1, background: C.border }}
                        />
                        <Step n={1} tint={C.primary} title="Créez le concours" body="Renseignez le format (tête-à-tête, doublette, triplette), le nombre de parties qualificatives et les terrains." />
                        <Step n={2} tint={C.accent} title="Inscrivez les équipes" body="Partagez le QR code. Les joueurs s’inscrivent, vous validez les présences et créez les équipes officielles." />
                        <Step n={3} tint={C.primary} title="Pilotez en direct" body="Lancez les qualifications, saisissez les scores sur tablette. Le système gère tirages, phases finales et podium." />
                    </div>
                </div>
            </section>

            {/* STATS */}
            <section className="px-8" style={{ background: 'oklch(0.26 0.13 240)', padding: '48px 32px' }}>
                <div className="mx-auto grid max-w-[1120px] grid-cols-2 gap-4 text-center md:grid-cols-4">
                    <Stat value="Multi-formats" label="tête-à-tête, doublette, triplette" />
                    <Stat value="Temps réel" label="tablette + écran TV" accent />
                    <Stat value="QR" label="inscriptions sans papier" />
                    <Stat value="0 €" label="pour les organisateurs" accent />
                </div>
            </section>

            {/* CTA FINAL */}
            <section className="px-8 text-center" style={{ padding: '80px 32px', background: DARK }}>
                <div className="mx-auto max-w-[560px]">
                    <h2 className="mb-4 text-[46px] font-extrabold text-white" style={{ fontFamily: DISPLAY, lineHeight: 1, letterSpacing: '0.01em' }}>
                        PRÊT À LANCER
                        <br />
                        <span style={{ color: C.accent }}>VOTRE CONCOURS ?</span>
                    </h2>
                    <p className="mb-8 text-base" style={{ color: 'oklch(0.62 0.04 250)', lineHeight: 1.6 }}>
                        Créez votre compte organisateur gratuitement. Votre premier concours sera en ligne en moins de
                        5 minutes.
                    </p>
                    <Link
                        href={primaryHref}
                        className="inline-flex items-center gap-2.5 rounded-[10px] px-9 py-4 text-base font-bold text-white"
                        style={{ background: C.accent, boxShadow: '0 4px 24px oklch(0.68 0.22 40 / 0.35)' }}
                    >
                        <Plus className="size-4" strokeWidth={2.5} />
                        Créer mon premier concours
                    </Link>
                </div>
            </section>

            {/* FOOTER */}
            <footer className="px-8 text-center" style={{ background: 'oklch(0.12 0.015 250)', padding: '28px 32px' }}>
                <div className="mb-2.5 flex items-center justify-center gap-2.5">
                    <div className="flex items-center justify-center rounded-full" style={{ width: 26, height: 26, background: C.accent }}>
                        <span className="size-2.5 rounded-full" style={{ background: 'oklch(0.96 0.025 40)' }} />
                    </div>
                    <span className="text-sm font-extrabold" style={{ fontFamily: DISPLAY, color: 'oklch(0.60 0.06 250)', letterSpacing: '0.04em' }}>
                        PÉTANQUE MANAGER
                    </span>
                </div>
                <p className="text-xs" style={{ color: 'oklch(0.40 0.04 250)' }}>
                    Pour les clubs et organisateurs de pétanque · Mentions légales · Confidentialité
                </p>
            </footer>
        </div>
    );
}

function QrIcon() {
    const b = 'oklch(0.42 0.16 240)';

    return (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="3" y="3" width="8" height="8" rx="1.5" stroke={b} strokeWidth="1.5" />
            <rect x="4.5" y="4.5" width="5" height="5" fill={b} rx="0.5" />
            <rect x="13" y="3" width="8" height="8" rx="1.5" stroke={b} strokeWidth="1.5" />
            <rect x="14.5" y="4.5" width="5" height="5" fill={b} rx="0.5" />
            <rect x="3" y="13" width="8" height="8" rx="1.5" stroke={b} strokeWidth="1.5" />
            <rect x="4.5" y="14.5" width="5" height="5" fill={b} rx="0.5" />
            <rect x="13" y="13" width="3" height="3" fill={b} rx="0.5" />
            <rect x="18" y="13" width="3" height="3" fill={b} rx="0.5" />
            <rect x="13" y="18" width="3" height="3" fill={b} rx="0.5" />
            <rect x="18" y="18" width="3" height="3" fill={b} rx="0.5" />
        </svg>
    );
}

function LiveIcon() {
    const o = 'oklch(0.60 0.22 40)';

    return (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 12c0-4.97 4.03-9 9-9s9 4.03 9 9-4.03 9-9 9-9-4.03-9-9z" stroke={o} strokeWidth="1.5" />
            <path d="M10 8.5l5 3.5-5 3.5V8.5z" fill={o} />
        </svg>
    );
}

function TvIcon() {
    const g = 'oklch(0.45 0.12 152)';

    return (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="4" width="20" height="13" rx="2" stroke={g} strokeWidth="1.5" />
            <path d="M9 21h6M12 17v4" stroke={g} strokeWidth="1.5" strokeLinecap="round" />
        </svg>
    );
}
