import { Head, Link } from '@inertiajs/react';
import { CircleCheck, Clock, Crown, Radar } from 'lucide-react';
import { BODY, C, DISPLAY } from '@/lib/petanque';

type Props = {
    tournamentName: string;
    followUrl: string;
    registration: {
        team_name: string | null;
        status: string;
        status_label: string;
        players: { first_name: string; last_name: string; is_captain: boolean }[];
    };
};

export default function Registered({ tournamentName, followUrl, registration }: Props) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center px-4 py-10" style={{ background: C.bg, fontFamily: BODY }}>
            <Head title="Demande d’inscription envoyée" />

            <div
                className="w-full max-w-md space-y-6 rounded-2xl p-8 text-center"
                style={{ background: C.card, border: `1px solid ${C.border}`, boxShadow: '0 2px 12px oklch(0 0 0 / 0.04)' }}
            >
                <div
                    className="mx-auto flex items-center justify-center rounded-full"
                    style={{ background: C.greenBg, color: C.green, width: 56, height: 56 }}
                >
                    <CircleCheck className="size-7" />
                </div>

                <div className="space-y-1.5">
                    <h1 className="text-[24px] font-extrabold" style={{ fontFamily: DISPLAY, color: C.ink }}>
                        Demande envoyée&nbsp;!
                    </h1>
                    <p className="text-sm" style={{ color: C.muted }}>
                        {registration.team_name ? (
                            <>
                                L’équipe{' '}
                                <strong style={{ color: C.ink }}>{registration.team_name}</strong>{' '}
                            </>
                        ) : (
                            'Votre équipe '
                        )}
                        a été enregistrée pour «&nbsp;{tournamentName}&nbsp;».
                    </p>
                </div>

                <div
                    className="flex items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-semibold"
                    style={{ background: C.amberBg, color: C.amberText }}
                >
                    <Clock className="size-4" />
                    {registration.status_label} — en attente de validation
                </div>

                <ul className="text-left text-sm" style={{ borderTop: `1px solid ${C.borderSoft}` }}>
                    {registration.players.map((player, index) => (
                        <li
                            key={index}
                            className="flex items-center gap-2 py-2.5"
                            style={{ borderBottom: `1px solid ${C.borderSoft}` }}
                        >
                            {player.is_captain && <Crown className="size-4 shrink-0" style={{ color: C.amber }} />}
                            <span style={{ color: C.ink, fontWeight: player.is_captain ? 600 : 400 }}>
                                {player.first_name} {player.last_name}
                            </span>
                            {player.is_captain && (
                                <span className="ml-auto text-xs" style={{ color: C.muted }}>
                                    Capitaine
                                </span>
                            )}
                        </li>
                    ))}
                </ul>

                <Link
                    href={followUrl}
                    className="flex w-full items-center justify-center gap-2 rounded-lg py-3.5 text-[15px] font-bold text-white"
                    style={{ background: C.primary }}
                >
                    <Radar className="size-4" />
                    Suivre mon équipe en direct
                </Link>

                <p className="text-xs" style={{ color: C.muted }}>
                    Présentez-vous le jour du concours pour valider votre présence.
                </p>
            </div>
        </div>
    );
}
