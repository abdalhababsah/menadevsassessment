import { Head } from '@inertiajs/react';

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

export default function InvitationLanding({ invitation }: Props) {
    return (
        <>
            <Head title="Quiz Invitation" />

            <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
                <div className="w-full max-w-lg">
                    <div className="rounded-xl bg-white p-8 shadow-sm ring-1 ring-gray-200">
                        <h1 className="text-2xl font-bold text-gray-900">{invitation.quiz.title}</h1>
                        {invitation.quiz.description && (
                            <p className="mt-2 text-sm text-gray-600">{invitation.quiz.description}</p>
                        )}

                        {invitation.email_domain_restriction && (
                            <div className="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                                Only candidates with an <strong>@{invitation.email_domain_restriction}</strong> email
                                may take this assessment.
                            </div>
                        )}

                        <div className="mt-6">
                            <p className="text-sm text-gray-700">
                                You&rsquo;ve been invited to take this assessment. Please continue to verify your email
                                and start the quiz.
                            </p>
                            <button
                                type="button"
                                disabled
                                className="mt-4 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white opacity-60"
                                title="Candidate auth flow is implemented in a later prompt"
                            >
                                Continue (candidate auth flow)
                            </button>
                            <p className="mt-2 text-xs text-gray-500">
                                The candidate sign-up / verification screens will be wired in the next phase.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
