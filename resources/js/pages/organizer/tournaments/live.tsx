import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Flag, MapPin, Pencil, Play, Swords, Trophy } from 'lucide-react';
import { useState } from 'react';
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

type MatchVM = {
    id: number;
    round: number;
    team_a: string | null;
    team_b: string | null;
    team_a_id: number | null;
    team_b_id: number | null;
    court: string | null;
    score_a: number | null;
    score_b: number | null;
    winner_team_id: number | null;
    status: string;
    is_walkover: boolean;
    is_forfeit: boolean;
};

type RoundVM = { round: number; label?: string; matches: MatchVM[] };
type StandingVM = { team: string; seed: number; wins: number; losses: number };
type DivisionVM = {
    label: string;
    rounds: RoundVM[];
    ranking: { position: number | null; team: string }[];
    complete: boolean;
};

type Props = {
    tournament: {
        id: number;
        name: string;
        status_label: string;
        current_phase: string | null;
        points_target: number;
        team_format_label: string;
        qualifying_rounds: number;
        tableaux_count: number;
    };
    counts: { teams: number; courts: number };
    canStartQualification: boolean;
    qualification: {
        currentRound: number;
        complete: boolean;
        rounds: RoundVM[];
        standings: StandingVM[];
    } | null;
    finals: DivisionVM[] | null;
};

function isActionable(status: string): boolean {
    return status === 'playing' || status === 'ready';
}

function TeamLine({
    name,
    isWinner,
    score,
}: {
    name: string | null;
    isWinner: boolean;
    score: number | null;
}) {
    return (
        <div className={`flex items-center justify-between gap-2 ${isWinner ? 'font-semibold' : ''}`}>
            <span className={name ? '' : 'text-muted-foreground italic'}>{name ?? 'À venir'}</span>
            {score !== null && <span className="tabular-nums">{score}</span>}
        </div>
    );
}

function MatchCard({
    match,
    onScore,
    onCorrect,
    onForfeit,
}: {
    match: MatchVM;
    onScore: (m: MatchVM) => void;
    onCorrect: (m: MatchVM) => void;
    onForfeit: (m: MatchVM) => void;
}) {
    if (match.status === 'bye') {
        const qualified = match.team_a ?? match.team_b;

        return (
            <div className="border-border rounded-lg border border-dashed p-3 text-sm">
                <p className="text-muted-foreground">Exempt</p>
                <p className="font-medium">{qualified} qualifié(e)</p>
            </div>
        );
    }

    const finished = match.status === 'finished';

    return (
        <div className="border-border space-y-2 rounded-lg border p-3">
            <div className="text-sm">
                <TeamLine
                    name={match.team_a}
                    isWinner={finished && match.winner_team_id === match.team_a_id}
                    score={match.score_a}
                />
                <div className="text-muted-foreground my-1 flex items-center gap-1 text-xs">
                    <Swords className="size-3" /> vs
                    {match.court && (
                        <span className="ml-auto flex items-center gap-1">
                            <MapPin className="size-3" />
                            Terrain {match.court}
                        </span>
                    )}
                </div>
                <TeamLine
                    name={match.team_b}
                    isWinner={finished && match.winner_team_id === match.team_b_id}
                    score={match.score_b}
                />
            </div>
            {isActionable(match.status) && match.team_a && match.team_b && (
                <div className="flex gap-1">
                    <Button size="sm" className="flex-1" onClick={() => onScore(match)}>
                        Saisir le score
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        className="text-muted-foreground"
                        onClick={() => onForfeit(match)}
                    >
                        <Flag />
                        Forfait
                    </Button>
                </div>
            )}
            {finished && (
                <div className="flex items-center gap-2">
                    {match.is_forfeit && (
                        <span className="text-muted-foreground text-xs font-medium">
                            Forfait
                        </span>
                    )}
                    <Button
                        size="sm"
                        variant="ghost"
                        className="text-muted-foreground ml-auto h-7"
                        onClick={() => onCorrect(match)}
                    >
                        <Pencil />
                        Corriger
                    </Button>
                </div>
            )}
        </div>
    );
}

export default function LiveTournament({
    tournament,
    counts,
    canStartQualification,
    qualification,
    finals,
}: Props) {
    useTournamentEcho(tournament.id);

    const showUrl = `/organizer/tournaments/${tournament.id}`;
    const [scoring, setScoring] = useState<MatchVM | null>(null);
    const [mode, setMode] = useState<'record' | 'correct'>('record');
    const [scoreA, setScoreA] = useState<number>(tournament.points_target);
    const [scoreB, setScoreB] = useState<number>(0);

    const openScore = (match: MatchVM) => {
        setMode('record');
        setScoreA(tournament.points_target);
        setScoreB(0);
        setScoring(match);
    };

    const openCorrect = (match: MatchVM) => {
        setMode('correct');
        setScoreA(match.score_a ?? tournament.points_target);
        setScoreB(match.score_b ?? 0);
        setScoring(match);
    };

    const submitScore = () => {
        if (!scoring) {
            return;
        }

        const options = { preserveScroll: true, onSuccess: () => setScoring(null) };
        const payload = { score_a: scoreA, score_b: scoreB };

        if (mode === 'correct') {
            router.patch(`/organizer/matches/${scoring.id}/result`, payload, options);
        } else {
            router.post(`/organizer/matches/${scoring.id}/result`, payload, options);
        }
    };

    const [forfeiting, setForfeiting] = useState<MatchVM | null>(null);
    const openForfeit = (match: MatchVM) => setForfeiting(match);
    const submitForfeit = (teamId: number | null) => {
        if (!forfeiting || teamId === null) {
            return;
        }

        router.post(
            `/organizer/matches/${forfeiting.id}/forfeit`,
            { forfeiting_team_id: teamId },
            { preserveScroll: true, onSuccess: () => setForfeiting(null) },
        );
    };

    // Le vainqueur change-t-il lors d'une correction ? (déclenche un recalcul)
    const winnerWouldChange =
        mode === 'correct' &&
        scoring !== null &&
        scoring.winner_team_id !== null &&
        (scoreA > scoreB ? scoring.team_a_id : scoring.team_b_id) !== scoring.winner_team_id;

    const post = (url: string) => router.post(url, {}, { preserveScroll: true });

    return (
        <>
            <Head title={`Déroulé — ${tournament.name}`} />

            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <Button asChild variant="ghost" size="sm" className="mb-1 -ml-2">
                            <Link href={showUrl}>
                                <ArrowLeft />
                                {tournament.name}
                            </Link>
                        </Button>
                        <h1 className="text-2xl font-semibold tracking-tight">Déroulé du concours</h1>
                        <p className="text-muted-foreground text-sm">
                            {tournament.team_format_label} · {tournament.qualifying_rounds} parties
                            qualificatives · {tournament.tableaux_count} tableau(x)
                        </p>
                    </div>
                    <Badge variant="secondary" className="text-sm">
                        {tournament.status_label}
                    </Badge>
                </div>

                {/* Étape 1 — lancement */}
                {tournament.current_phase === null && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Prêt à lancer&nbsp;?</CardTitle>
                            <CardDescription>
                                {counts.teams} équipe(s) officielle(s) · {counts.courts} terrain(s).
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {!canStartQualification && (
                                <p className="text-muted-foreground text-sm">
                                    Il faut au moins 2 équipes officielles et 1 terrain. Validez les
                                    présences puis créez les équipes depuis les inscriptions.
                                </p>
                            )}
                            <Button
                                disabled={!canStartQualification}
                                onClick={() => post(`${showUrl}/qualification/start`)}
                            >
                                <Play />
                                Lancer les qualifications
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {/* Qualifications */}
                {qualification && (
                    <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-lg font-semibold">Qualifications</h2>
                                {qualification.complete &&
                                    tournament.current_phase === 'qualification' && (
                                        <Button onClick={() => post(`${showUrl}/finals/start`)}>
                                            <Flag />
                                            Lancer les phases finales
                                        </Button>
                                    )}
                            </div>
                            {qualification.rounds.map((round) => (
                                <Card key={round.round}>
                                    <CardHeader>
                                        <CardTitle className="text-base">
                                            Ronde {round.round}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="grid gap-3 sm:grid-cols-2">
                                        {round.matches.map((match) => (
                                            <MatchCard
                                                key={match.id}
                                                match={match}
                                                onScore={openScore}
                                                onCorrect={openCorrect}
                                                onForfeit={openForfeit}
                                            />
                                        ))}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        <div>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Classement</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="text-muted-foreground border-border border-b text-left text-xs">
                                                <th className="px-4 py-2">Équipe</th>
                                                <th className="px-2 py-2 text-center">V</th>
                                                <th className="px-4 py-2 text-center">D</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {qualification.standings.map((s, index) => (
                                                <tr
                                                    key={index}
                                                    className="border-border/60 border-b last:border-0"
                                                >
                                                    <td className="px-4 py-1.5">{s.team}</td>
                                                    <td className="px-2 py-1.5 text-center font-medium tabular-nums">
                                                        {s.wins}
                                                    </td>
                                                    <td className="text-muted-foreground px-4 py-1.5 text-center tabular-nums">
                                                        {s.losses}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}

                {/* Phases finales */}
                {finals && (
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Phases finales</h2>
                        {finals.map((division) => (
                            <Card key={division.label}>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Trophy className="size-4" />
                                        Tableau {division.label}
                                        {division.complete && (
                                            <Badge variant="default" className="ml-2">
                                                Terminé
                                            </Badge>
                                        )}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex gap-4 overflow-x-auto pb-2">
                                        {division.rounds.map((round) => (
                                            <div
                                                key={round.round}
                                                className="w-56 shrink-0 space-y-3"
                                            >
                                                <p className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">
                                                    {round.label}
                                                </p>
                                                {round.matches.map((match) => (
                                                    <MatchCard
                                                        key={match.id}
                                                        match={match}
                                                        onScore={openScore}
                                                        onCorrect={openCorrect}
                                                        onForfeit={openForfeit}
                                                    />
                                                ))}
                                            </div>
                                        ))}
                                    </div>

                                    {division.complete && (
                                        <div className="bg-muted/40 rounded-lg p-3">
                                            <p className="mb-2 text-sm font-medium">
                                                Classement final
                                            </p>
                                            <ol className="space-y-1 text-sm">
                                                {division.ranking.map((r, index) => (
                                                    <li
                                                        key={index}
                                                        className="flex items-center gap-2"
                                                    >
                                                        <span className="text-muted-foreground w-6 tabular-nums">
                                                            {r.position ?? '—'}
                                                        </span>
                                                        <span
                                                            className={
                                                                r.position === 1
                                                                    ? 'font-semibold'
                                                                    : ''
                                                            }
                                                        >
                                                            {r.team}
                                                        </span>
                                                        {r.position === 1 && (
                                                            <Trophy className="size-3.5 text-amber-500" />
                                                        )}
                                                    </li>
                                                ))}
                                            </ol>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Saisie du score */}
            <Dialog open={scoring !== null} onOpenChange={(open) => !open && setScoring(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {mode === 'correct' ? 'Corriger le score' : 'Saisir le score'}
                        </DialogTitle>
                        <DialogDescription>
                            Le vainqueur doit atteindre {tournament.points_target} points.
                        </DialogDescription>
                    </DialogHeader>
                    {scoring && (
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="score_a">{scoring.team_a}</Label>
                                <Input
                                    id="score_a"
                                    type="number"
                                    min={0}
                                    max={tournament.points_target}
                                    value={scoreA}
                                    onChange={(e) => setScoreA(Number(e.target.value))}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="score_b">{scoring.team_b}</Label>
                                <Input
                                    id="score_b"
                                    type="number"
                                    min={0}
                                    max={tournament.points_target}
                                    value={scoreB}
                                    onChange={(e) => setScoreB(Number(e.target.value))}
                                />
                            </div>
                        </div>
                    )}
                    {winnerWouldChange && (
                        <p className="rounded-md bg-amber-500/10 p-2 text-center text-sm text-amber-700 dark:text-amber-400">
                            Le vainqueur change : le concours sera recalculé à partir de cette
                            partie.
                        </p>
                    )}
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setScoring(null)}>
                            Annuler
                        </Button>
                        <Button onClick={submitScore}>
                            {mode === 'correct' ? 'Corriger' : 'Valider'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Forfait */}
            <Dialog open={forfeiting !== null} onOpenChange={(open) => !open && setForfeiting(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Déclarer un forfait</DialogTitle>
                        <DialogDescription>
                            Quelle équipe déclare forfait&nbsp;? L’adversaire l’emporte{' '}
                            {tournament.points_target}-0.
                        </DialogDescription>
                    </DialogHeader>
                    {forfeiting && (
                        <div className="grid gap-2">
                            <Button
                                variant="outline"
                                onClick={() => submitForfeit(forfeiting.team_a_id)}
                            >
                                {forfeiting.team_a} déclare forfait
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => submitForfeit(forfeiting.team_b_id)}
                            >
                                {forfeiting.team_b} déclare forfait
                            </Button>
                        </div>
                    )}
                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setForfeiting(null)}>
                            Annuler
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Concours', href: '/organizer/tournaments' },
    { title: 'Déroulé', href: '#' },
];

LiveTournament.layout = { breadcrumbs };
