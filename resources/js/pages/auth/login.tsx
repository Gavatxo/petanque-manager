import { Form, Head } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { C } from '@/lib/petanque';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Connexion" />

            <PasskeyVerify />

            {status && (
                <div className="mb-4 text-center text-sm font-medium" style={{ color: C.green }}>
                    {status}
                </div>
            )}

            <Form {...store.form()} resetOnSuccess={['password']} className="flex flex-col gap-[18px]">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Adresse e-mail</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="jean@boulodrome.fr"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Mot de passe</Label>
                                {canResetPassword && (
                                    <TextLink href={request()} className="ml-auto text-xs" tabIndex={5}>
                                        Mot de passe oublié ?
                                    </TextLink>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="••••••••"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center space-x-3">
                            <Checkbox id="remember" name="remember" tabIndex={3} />
                            <Label htmlFor="remember" className="font-normal">
                                Se souvenir de moi
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            className="mt-1 w-full py-[13px] font-bold text-white hover:brightness-95"
                            style={{ background: C.primary }}
                            tabIndex={4}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            Se connecter
                        </Button>
                    </>
                )}
            </Form>

            <div
                className="mt-5 rounded-[9px] p-4"
                style={{ background: C.primarySoft, border: '1px solid oklch(0.87 0.06 240)' }}
            >
                <div
                    className="mb-1.5 flex items-center gap-1.5 text-xs font-bold"
                    style={{ color: 'oklch(0.35 0.12 240)' }}
                >
                    <KeyRound className="size-3.5" />
                    Connexion par clé d’accès (passkey)
                </div>
                <p className="text-xs" style={{ color: 'oklch(0.45 0.08 240)' }}>
                    Utilisez la biométrie ou votre code appareil pour vous connecter sans mot de passe.
                </p>
            </div>
        </>
    );
}

Login.layout = {
    title: 'Bon retour !',
    description: 'Connectez-vous à votre espace organisateur.',
    tab: 'login',
};
