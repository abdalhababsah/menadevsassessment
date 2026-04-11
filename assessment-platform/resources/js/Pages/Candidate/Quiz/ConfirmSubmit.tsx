import CandidateLayout from '@/Layouts/CandidateLayout';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

type Props = {
    quiz: { id: number; title: string };
    attempt: { id: number };
    counts: {
        total_questions: number;
        answered: number;
        unanswered: number;
    };
};

export default function ConfirmSubmit({ quiz, counts }: Props) {
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleFinalSubmit = async () => {
        if (submitting) {
            return;
        }
        setSubmitting(true);
        setError(null);

        try {
            const { data } = await axios.post<{
                submitted: boolean;
                exit_fullscreen: boolean;
                redirect: string;
            }>('/quiz/final-submit');

            if (data.exit_fullscreen && document.fullscreenElement !== null) {
                try {
                    await document.exitFullscreen();
                } catch {
                    // Ignore — best-effort.
                }
            }

            router.visit(data.redirect);
        } catch (requestError) {
            setSubmitting(false);
            setError(
                requestError instanceof Error
                    ? requestError.message
                    : 'Failed to submit your assessment. Please try again.',
            );
        }
    };

    const handleReturn = () => {
        router.visit('/quiz/run');
    };

    const hasUnanswered = counts.unanswered > 0;

    return (
        <CandidateLayout>
            <Head title={`Submit — ${quiz.title}`} />

            <div className="mx-auto max-w-xl pb-16">
                <div className="rounded-3xl border border-gray-200 bg-white p-8 shadow-sm">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.28em] text-gray-500">
                        Final step
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-gray-950">
                        Submit your assessment?
                    </h1>
                    <p className="mt-3 text-sm leading-relaxed text-gray-600">
                        Once you submit, you won't be able to return to the assessment or change
                        your answers. Please review the summary below before confirming.
                    </p>

                    <dl className="mt-6 grid grid-cols-3 gap-3 rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <div>
                            <dt className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                Total
                            </dt>
                            <dd className="mt-1 text-2xl font-semibold text-gray-950">
                                {counts.total_questions}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[10px] font-semibold uppercase tracking-wide text-emerald-600">
                                Answered
                            </dt>
                            <dd className="mt-1 text-2xl font-semibold text-emerald-700">
                                {counts.answered}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[10px] font-semibold uppercase tracking-wide text-amber-600">
                                Unanswered
                            </dt>
                            <dd className="mt-1 text-2xl font-semibold text-amber-700">
                                {counts.unanswered}
                            </dd>
                        </div>
                    </dl>

                    {hasUnanswered && (
                        <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                            You have {counts.unanswered} unanswered question
                            {counts.unanswered === 1 ? '' : 's'}. Unanswered questions are scored
                            as zero.
                        </div>
                    )}

                    {error && (
                        <div className="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                            {error}
                        </div>
                    )}

                    <div className="mt-8 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            onClick={handleReturn}
                            disabled={submitting}
                            className="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Keep working
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                void handleFinalSubmit();
                            }}
                            disabled={submitting}
                            className="rounded-xl bg-gray-950 px-5 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {submitting ? 'Submitting…' : 'Submit assessment'}
                        </button>
                    </div>
                </div>
            </div>
        </CandidateLayout>
    );
}
