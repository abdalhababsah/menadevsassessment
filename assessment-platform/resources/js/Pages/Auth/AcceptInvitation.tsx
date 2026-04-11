import InputError from '@/components/inputerror';
import InputLabel from '@/components/inputlabel';
import PrimaryButton from '@/components/primarybutton';
import TextInput from '@/components/textinput';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Props {
    token: string;
    email: string;
    name: string;
}

export default function AcceptInvitation({ token, email, name }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('invitations.store', token));
    };

    return (
        <>
            <Head title="Accept Invitation" />

            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <h1 className="text-2xl font-bold tracking-tight text-gray-900">
                            Assessment Platform
                        </h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Welcome, {name}! Set your password to get started.
                        </p>
                    </div>

                    <div className="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
                        <div className="mb-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                            <strong>Email:</strong> {email}
                        </div>

                        <form onSubmit={submit}>
                            <div>
                                <InputLabel htmlFor="password" value="Password" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    isFocused
                                    required
                                />
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div className="mt-4">
                                <InputLabel htmlFor="password_confirmation" value="Confirm Password" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    required
                                />
                            </div>

                            <PrimaryButton
                                className="mt-6 w-full justify-center"
                                disabled={processing}
                            >
                                Set Password & Sign In
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
