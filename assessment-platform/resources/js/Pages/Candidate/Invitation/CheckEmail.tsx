import CandidateLayout from '@/Layouts/CandidateLayout';
import { Head } from '@inertiajs/react';

export default function CheckEmail({ email }: { email: string }) {
    return (
        <CandidateLayout>
            <Head title="Check your email" />

            <div className="mx-auto max-w-lg">
                <div className="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-200">
                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 className="mt-4 text-xl font-bold text-gray-900">Check your email</h1>
                    <p className="mt-2 text-sm text-gray-600">
                        We&rsquo;ve sent a verification link to <strong>{email}</strong>. Click the link in the
                        email to continue to the assessment.
                    </p>
                    <p className="mt-4 text-xs text-gray-500">
                        The link will expire in 24 hours. Check your spam folder if you don&rsquo;t see it.
                    </p>
                </div>
            </div>
        </CandidateLayout>
    );
}
