import { Form, router } from '@inertiajs/react';
import { Ban, CircleCheck, Plus, Trash2, Wand2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Court } from '@/types';

type Props = {
    tournamentId: number;
    courts: Court[];
};

export function CourtsManager({ tournamentId, courts }: Props) {
    const [generateCount, setGenerateCount] = useState(4);

    const generate = () => {
        router.post(
            `/organizer/tournaments/${tournamentId}/courts/generate`,
            { count: generateCount },
            { preserveScroll: true },
        );
    };

    const toggleStatus = (court: Court) => {
        router.patch(
            `/organizer/courts/${court.id}`,
            { status: court.status === 'disabled' ? 'available' : 'disabled' },
            { preserveScroll: true },
        );
    };

    const remove = (court: Court) => {
        router.delete(`/organizer/courts/${court.id}`, { preserveScroll: true });
    };

    return (
        <div className="space-y-5">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <Form
                    action={`/organizer/tournaments/${tournamentId}/courts`}
                    method="post"
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                    className="flex items-end gap-2"
                >
                    {({ processing, errors }) => (
                        <div className="grid gap-2">
                            <Label htmlFor="label">Ajouter un terrain</Label>
                            <div className="flex items-start gap-2">
                                <div className="grid gap-1">
                                    <Input
                                        id="label"
                                        name="label"
                                        placeholder="N° ou nom"
                                        className="w-40"
                                    />
                                    <InputError message={errors.label} />
                                </div>
                                <Button type="submit" size="icon" disabled={processing}>
                                    <Plus />
                                    <span className="sr-only">Ajouter</span>
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>

                <div className="grid gap-2">
                    <Label htmlFor="generate-count">Générer en série</Label>
                    <div className="flex items-center gap-2">
                        <Input
                            id="generate-count"
                            type="number"
                            min={1}
                            max={50}
                            value={generateCount}
                            onChange={(event) =>
                                setGenerateCount(Number(event.target.value))
                            }
                            className="w-24"
                        />
                        <Button type="button" variant="secondary" onClick={generate}>
                            <Wand2 />
                            Générer
                        </Button>
                    </div>
                </div>
            </div>

            {courts.length === 0 ? (
                <p className="text-muted-foreground border-border rounded-lg border border-dashed p-6 text-center text-sm">
                    Aucun terrain. Ajoutez-en un ou générez une série numérotée.
                </p>
            ) : (
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                    {courts.map((court) => (
                        <div
                            key={court.id}
                            className="border-border flex items-center justify-between gap-2 rounded-lg border px-3 py-2"
                        >
                            <div className="min-w-0">
                                <p className="truncate font-medium">Terrain {court.label}</p>
                                <Badge
                                    variant={
                                        court.status === 'disabled' ? 'outline' : 'secondary'
                                    }
                                    className="mt-1"
                                >
                                    {court.status_label}
                                </Badge>
                            </div>
                            <div className="flex shrink-0 items-center">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title={
                                        court.status === 'disabled'
                                            ? 'Réactiver'
                                            : 'Désactiver'
                                    }
                                    onClick={() => toggleStatus(court)}
                                >
                                    {court.status === 'disabled' ? (
                                        <CircleCheck />
                                    ) : (
                                        <Ban />
                                    )}
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    title="Supprimer"
                                    onClick={() => remove(court)}
                                >
                                    <Trash2 className="text-destructive" />
                                </Button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
