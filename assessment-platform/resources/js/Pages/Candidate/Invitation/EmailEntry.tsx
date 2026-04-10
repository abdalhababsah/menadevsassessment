import CandidateLayout from '@/Layouts/CandidateLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Props {
    invitation: {
        token: string;
        quiz: {
            id: number;
            title: string;
            description: string | null;
        };
        email_domain_restriction: string | null;
    };
}

export default function EmailEntry({ invitation }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('candidate.email.submit'));
    };

    return (
        <CandidateLayout>
            <Head title="Start Assessment" />

            <div className="mx-auto max-w-lg">
                <div className="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
                    <h1 className="text-2xl font-bold text-gray-900">{invitation.quiz.title}</h1>
                    {invitation.quiz.description && (
                        <p className="mt-2 text-sm text-gray-600">{invitation.quiz.description}</p>
                    )}

                    {invitation.email_domain_restriction && (
                        <div className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                            Only candidates with an <strong>@{invitation.email_domain_restriction}</strong> email
                            address can take this assessment.
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-6">
                        <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                            Email address
                        </label>
                        <input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            autoFocus
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                            placeholder="you@example.com"
                        />
                        {errors.email && <p className="mt-2 text-sm text-red-600">{errors.email}</p>}

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-6 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                        >
                            Continue
                        </button>
                    </form>

                    <p className="mt-4 text-center text-xs text-gray-500">
                        We&rsquo;ll send a one-time link to verify your email.{' '}
                        <Link href={route('candidate.register')} className="font-medium text-indigo-600 hover:text-indigo-800">
                            Or create an account
                        </Link>
                    </p>
                </div>
            </div>
        </CandidateLayout>
    );
}
