import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { BODY, C, DISPLAY } from '@/lib/petanque';
import { login, register } from '@/routes';

type Props = {
    title?: string;
    description?: string;
    /** Affiche le sélecteur Connexion / Inscription (pages login & register uniquement). */
    tab?: 'login' | 'register';
    children: React.ReactNode;
};

const FEATURES = [
    'Inscriptions par QR code, sans papier',
    'Tableau de bord live sur tablette',
    'Affichage TV pour le boulodrome',
];

function BoulesLogo() {
    return (
        <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="10.5" cy="10.5" r="8" fill="oklch(0.96 0.025 40)" />
            <circle cx="8" cy="9" r="1.5" fill="oklch(0.60 0.20 40)" opacity="0.65" />
            <circle cx="13.5" cy="8" r="1.3" fill="oklch(0.60 0.20 40)" opacity="0.65" />
            <circle cx="14" cy="13.5" r="1.5" fill="oklch(0.60 0.20 40)" opacity="0.65" />
        </svg>
    );
}

export default function AuthLayout({ title = '', description = '', tab, children }: Props) {
    return (
        <div className="flex min-h-svh" style={{ background: C.bg, fontFamily: BODY }}>
            {/* Panneau de marque */}
            <aside
                className="relative hidden w-2/5 shrink-0 flex-col justify-between overflow-hidden p-10 lg:flex"
                style={{ background: 'oklch(0.16 0.02 250)' }}
            >
                <div
                    className="pointer-events-none absolute inset-0"
                    style={{
                        backgroundImage: 'radial-gradient(oklch(0.42 0.16 240 / 0.10) 1px, transparent 1px)',
                        backgroundSize: '24px 24px',
                    }}
                />
                <div
                    className="pointer-events-none absolute rounded-full"
                    style={{ bottom: -60, right: -60, width: 280, height: 280, background: 'oklch(0.42 0.16 240 / 0.08)' }}
                />
                <div
                    className="pointer-events-none absolute rounded-full"
                    style={{ top: '30%', left: -40, width: 180, height: 180, background: 'oklch(0.68 0.22 40 / 0.06)' }}
                />

                {/* Logo */}
                <div className="relative z-10 flex items-center gap-2.5">
                    <div
                        className="flex items-center justify-center rounded-full"
                        style={{ width: 38, height: 38, background: C.accent, boxShadow: '0 2px 12px oklch(0.68 0.22 40 / 0.4)' }}
                    >
                        <BoulesLogo />
                    </div>
                    <div>
                        <div
                            className="text-[17px] font-extrabold text-white"
                            style={{ fontFamily: DISPLAY, letterSpacing: '0.04em', lineHeight: 1.1 }}
                        >
                            PÉTANQUE
                        </div>
                        <div
                            className="text-[11px] font-medium uppercase"
                            style={{ color: 'oklch(0.55 0.08 240)', letterSpacing: '0.09em' }}
                        >
                            Manager
                        </div>
                    </div>
                </div>

                {/* Accroche */}
                <div className="relative z-10">
                    <h1
                        className="mb-4 font-extrabold text-white"
                        style={{ fontFamily: DISPLAY, fontSize: 'clamp(36px,4vw,52px)', lineHeight: 1.05, letterSpacing: '0.01em', textWrap: 'balance' }}
                    >
                        VOTRE ESPACE
                        <br />
                        ORGANISATEUR
                    </h1>
                    <p className="mb-8 max-w-80 text-[15px]" style={{ color: 'oklch(0.60 0.04 250)', lineHeight: 1.65 }}>
                        Gérez vos concours de pétanque : inscriptions QR, résultats live, classements automatiques,
                        écran TV.
                    </p>
                    <div className="flex flex-col gap-3">
                        {FEATURES.map((feature) => (
                            <div key={feature} className="flex items-center gap-2.5">
                                <div
                                    className="flex shrink-0 items-center justify-center rounded-full"
                                    style={{
                                        width: 26,
                                        height: 26,
                                        background: 'oklch(0.42 0.16 240 / 0.2)',
                                        border: '1px solid oklch(0.42 0.16 240 / 0.3)',
                                    }}
                                >
                                    <Check className="size-3" style={{ color: 'oklch(0.65 0.12 240)' }} strokeWidth={2.5} />
                                </div>
                                <span className="text-[13px]" style={{ color: 'oklch(0.65 0.04 250)' }}>
                                    {feature}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                <p className="relative z-10 text-xs" style={{ color: 'oklch(0.40 0.04 250)' }}>
                    Gratuit pour les organisateurs · Fait pour les clubs
                </p>
            </aside>

            {/* Panneau formulaire */}
            <main className="flex flex-1 items-center justify-center px-8 py-10">
                <div className="w-full max-w-[400px]">
                    {tab && (
                        <div
                            className="mb-8 flex gap-0 rounded-[9px] p-[3px]"
                            style={{ background: 'oklch(0.92 0.012 65)' }}
                        >
                            <TabLink href={login()} label="Connexion" active={tab === 'login'} />
                            <TabLink href={register()} label="Inscription" active={tab === 'register'} />
                        </div>
                    )}

                    {(title || description) && (
                        <div className="mb-7">
                            {title && (
                                <h2
                                    className="mb-1 text-[28px] font-extrabold"
                                    style={{ fontFamily: DISPLAY, color: C.ink, letterSpacing: '0.01em' }}
                                >
                                    {title}
                                </h2>
                            )}
                            {description && (
                                <p className="text-sm" style={{ color: C.muted }}>
                                    {description}
                                </p>
                            )}
                        </div>
                    )}

                    {children}
                </div>
            </main>
        </div>
    );
}

function TabLink({ href, label, active }: { href: ReturnType<typeof login>; label: string; active: boolean }) {
    return (
        <Link
            href={href}
            className="flex-1 rounded-[7px] py-2.5 text-center text-sm font-semibold transition-colors"
            style={{
                background: active ? C.card : 'transparent',
                color: active ? C.ink : C.muted,
                boxShadow: active ? '0 1px 4px oklch(0 0 0 / 0.08)' : 'none',
            }}
        >
            {label}
        </Link>
    );
}
