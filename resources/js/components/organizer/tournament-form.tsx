import { Form, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { SelectOption } from '@/types';

export type TournamentFormValues = {
    name: string;
    description: string;
    location: string;
    scheduled_at: string;
    team_format: string;
    qualifying_rounds: number;
    tableaux_count: number;
    points_target: number;
    max_teams: string;
    status?: string;
};

type Props = {
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
    formats: SelectOption[];
    statuses?: SelectOption[];
    values: TournamentFormValues;
};

export function TournamentForm({
    action,
    method,
    submitLabel,
    cancelUrl,
    formats,
    statuses,
    values,
}: Props) {
    return (
        <Form
            action={action}
            method={method}
            options={{ preserveScroll: true }}
            className="space-y-8"
        >
            {({ processing, errors }) => (
                <>
                    <section className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nom du concours</Label>
                            <Input
                                id="name"
                                name="name"
                                defaultValue={values.name}
                                required
                                autoFocus
                                placeholder="Ex. Concours du 14 juillet"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                name="description"
                                defaultValue={values.description}
                                rows={3}
                                placeholder="Informations complémentaires (facultatif)"
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="location">Lieu</Label>
                                <Input
                                    id="location"
                                    name="location"
                                    defaultValue={values.location}
                                    placeholder="Boulodrome, ville…"
                                />
                                <InputError message={errors.location} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_at">Date &amp; heure</Label>
                                <Input
                                    id="scheduled_at"
                                    name="scheduled_at"
                                    type="datetime-local"
                                    defaultValue={values.scheduled_at}
                                />
                                <InputError message={errors.scheduled_at} />
                            </div>
                        </div>
                    </section>

                    <section className="space-y-4">
                        <div>
                            <h2 className="text-sm font-semibold">Format</h2>
                            <p className="text-muted-foreground text-sm">
                                Ces réglages piloteront le moteur d’appariement.
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="team_format">Type d’équipe</Label>
                                <Select name="team_format" defaultValue={values.team_format}>
                                    <SelectTrigger id="team_format">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {formats.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.team_format} />
                            </div>

                            {statuses && (
                                <div className="grid gap-2">
                                    <Label htmlFor="status">Statut</Label>
                                    <Select name="status" defaultValue={values.status}>
                                        <SelectTrigger id="status">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statuses.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status} />
                                </div>
                            )}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="grid gap-2">
                                <Label htmlFor="qualifying_rounds">Parties qualif.</Label>
                                <Input
                                    id="qualifying_rounds"
                                    name="qualifying_rounds"
                                    type="number"
                                    min={1}
                                    max={12}
                                    defaultValue={values.qualifying_rounds}
                                    required
                                />
                                <InputError message={errors.qualifying_rounds} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tableaux_count">Tableaux</Label>
                                <Input
                                    id="tableaux_count"
                                    name="tableaux_count"
                                    type="number"
                                    min={1}
                                    max={4}
                                    defaultValue={values.tableaux_count}
                                    required
                                />
                                <InputError message={errors.tableaux_count} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="points_target">Points à atteindre</Label>
                                <Input
                                    id="points_target"
                                    name="points_target"
                                    type="number"
                                    min={1}
                                    max={21}
                                    defaultValue={values.points_target}
                                    required
                                />
                                <InputError message={errors.points_target} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="max_teams">Équipes max.</Label>
                                <Input
                                    id="max_teams"
                                    name="max_teams"
                                    type="number"
                                    min={2}
                                    max={512}
                                    defaultValue={values.max_teams}
                                    placeholder="Illimité"
                                />
                                <InputError message={errors.max_teams} />
                            </div>
                        </div>
                    </section>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {submitLabel}
                        </Button>
                        <Button asChild variant="ghost" type="button">
                            <Link href={cancelUrl}>Annuler</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
