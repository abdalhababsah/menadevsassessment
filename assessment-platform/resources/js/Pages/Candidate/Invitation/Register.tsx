import CandidateLayout from '@/Layouts/CandidateLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Props {
    invitation: {
        token: string;
        quiz: { title: string };
        email_domain_restriction: string | null;
    };
}

export default function Register({ invitation }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('candidate.register'));
    };

    return (
        <CandidateLayout>
            <Head title="Create account" />

            <div className="mx-auto max-w-lg">
                <div className="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
                    <h1 className="text-2xl font-bold text-gray-900">Create your account</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        for <strong>{invitation.quiz.title}</strong>
                    </p>

                    {invitation.email_domain_restriction && (
                        <div className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                            Use an <strong>@{invitation.email_domain_restriction}</strong> email address.
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-6 space-y-4">
                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-gray-700">Full name</label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoFocus
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                            />
                            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                        </div>

                        <div>
                            <label htmlFor="email" className="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                            />
                            {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email}</p>}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-sm font-medium text-gray-700">Password</label>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                            />
                            {errors.password && <p className="mt-1 text-xs text-red-600">{errors.password}</p>}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-700">Confirm password</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                required
                                className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Create account & start
                        </button>
                    </form>

                    <p className="mt-4 text-center text-xs text-gray-500">
                        Or continue with{' '}
                        <Link href={route('candidate.invitations.show', invitation.token)} className="font-medium text-indigo-600 hover:text-indigo-800">
                            email-only
                        </Link>
                    </p>
                </div>
            </div>
        </CandidateLayout>
    );
}
