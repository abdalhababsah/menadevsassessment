import CandidateLayout from '@/Layouts/CandidateLayout';
import { Head } from '@inertiajs/react';

type Props = {
    quiz: { id: number; title: string };
    attempt: {
        id: number;
        status: string;
        submitted_at: string | null;
    };
};

export default function Submitted({ quiz, attempt }: Props) {
    const submittedDate = attempt.submitted_at
        ? new Date(attempt.submitted_at).toLocaleString()
        : null;

    return (
        <CandidateLayout>
            <Head title={`Submitted — ${quiz.title}`} />

            <div className="mx-auto flex min-h-[60vh] max-w-xl items-center justify-center pb-16">
                <div className="w-full rounded-3xl border border-emerald-200 bg-white p-10 text-center shadow-sm">
                    <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                        <svg
                            className="h-7 w-7 text-emerald-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            strokeWidth={2}
                            stroke="currentColor"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="m4.5 12.75 6 6 9-13.5"
                            />
                        </svg>
                    </div>

                    <h1 className="mt-6 text-2xl font-semibold text-gray-950">
                        Your responses have been submitted
                    </h1>
                    <p className="mt-3 text-sm leading-relaxed text-gray-600">
                        Thank you for completing <span className="font-medium text-gray-900">{quiz.title}</span>.
                        You can now close this window.
                    </p>

                    {submittedDate && (
                        <p className="mt-6 text-[11px] font-medium uppercase tracking-widest text-gray-500">
                            Submitted {submittedDate}
                        </p>
                    )}
                </div>
            </div>
        </CandidateLayout>
    );
}
