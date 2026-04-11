import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

type Criterion = {
    id: number;
    name: string;
    description: string | null;
    scale_labels: Record<string, string>;
};

type Evaluation = {
    criterion_id: number;
    response_side: string;
    rating_value: string;
    justification: string | null;
};

type FormResponse = {
    stage: string | null;
    field_key: string;
    value: string;
};

type Turn = {
    id: number;
    turn_number: number;
    candidate_input: string | null;
    response_a: string | null;
    response_b: string | null;
    model_a: string;
    model_b: string;
    sxs_rating: number | null;
    sxs_justification: string | null;
    selected_side: string | null;
    selected_response_rewrite: string | null;
    completed_at: string | null;
    evaluations: Evaluation[];
    form_responses: FormResponse[];
};

type Review = {
    id: number;
    score: number;
    decision: string;
    comments: string | null;
    finalized: boolean;
    reviewer_name: string | null;
};

type Props = {
    answer: {
        id: number;
        status: string;
        question_stem: string;
        question_points: number;
    };
    attempt: { id: number; status: string };
    quiz: { id: number; title: string };
    candidate: { name: string | null; email: string | null };
    criteria: Criterion[];
    turns: Turn[];
    review: Review | null;
    permissions: {
        can_score: boolean;
        can_finalize: boolean;
    };
};

const DECISION_OPTIONS: Array<{ value: string; label: string }> = [
    { value: 'accepted', label: 'Accepted' },
    { value: 'partially_accepted', label: 'Partially accepted' },
    { value: 'rejected', label: 'Rejected' },
];

export default function RlhfReview({
    answer,
    attempt,
    quiz,
    candidate,
    criteria,
    turns,
    review,
    permissions,
}: Props) {
    const [score, setScore] = useState<number>(review?.score ?? 0);
    const [decision, setDecision] = useState<string>(review?.decision ?? 'accepted');
    const [comments, setComments] = useState<string>(review?.comments ?? '');
    const [finalized, setFinalized] = useState<boolean>(review?.finalized ?? false);
    const [saving, setSaving] = useState(false);
    const [finalizing, setFinalizing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [feedback, setFeedback] = useState<string | null>(null);

    const criterionById = new Map(criteria.map((c) => [c.id, c] as const));
    const locked = finalized;

    const saveDraft = async () => {
        if (saving) return;
        setSaving(true);
        setError(null);
        setFeedback(null);
        try {
            await axios.post(`/admin/rlhf/review/${answer.id}`, {
                score,
                decision,
                comments: comments.length > 0 ? comments : null,
            });
            setFeedback('Draft saved.');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Save failed.');
        } finally {
            setSaving(false);
        }
    };

    const finalize = async () => {
        if (finalizing) return;
        setFinalizing(true);
        setError(null);
        setFeedback(null);
        try {
            // Always save first, then finalize.
            await axios.post(`/admin/rlhf/review/${answer.id}`, {
                score,
                decision,
                comments: comments.length > 0 ? comments : null,
            });
            await axios.post(`/admin/rlhf/review/${answer.id}/finalize`);
            setFinalized(true);
            setFeedback('Review finalized.');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Finalize failed.');
        } finally {
            setFinalizing(false);
        }
    };

    const sideLabel = (side: string | null): string => {
        if (side === 'a') return 'Response A';
        if (side === 'b') return 'Response B';
        return '—';
    };

    return (
        <AdminLayout>
            <Head title={`Review — ${candidate.name ?? quiz.title}`} />

            <div className="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[1fr,320px]">
                <div className="space-y-6">
                    <Link
                        href={`/admin/results/attempt/${attempt.id}`}
                        className="text-[11px] font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-700"
                    >
                        ← Attempt #{attempt.id}
                    </Link>

                    <header className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.28em] text-gray-500">
                            RLHF review
                        </p>
                        <h1 className="mt-1 text-xl font-semibold text-gray-950">{answer.question_stem}</h1>
                        <p className="mt-2 text-xs text-gray-600">
                            {candidate.name ?? 'Candidate'} · {candidate.email} · {quiz.title}
                        </p>
                    </header>

                    {turns.map((turn) => (
                        <article
                            key={turn.id}
                            id={`turn-${turn.turn_number}`}
                            className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <header className="flex items-center justify-between">
                                <h2 className="text-base font-semibold text-gray-950">
                                    Turn {turn.turn_number}
                                </h2>
                                {turn.selected_side && (
                                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                        Selected: {sideLabel(turn.selected_side)}
                                    </span>
                                )}
                            </header>

                            {turn.candidate_input && (
                                <section className="mt-4">
                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Candidate prompt
                                    </p>
                                    <p className="mt-1 whitespace-pre-wrap text-sm text-gray-800">
                                        {turn.candidate_input}
                                    </p>
                                </section>
                            )}

                            <div className="mt-4 grid gap-3 md:grid-cols-2">
                                {(['a', 'b'] as const).map((side) => {
                                    const text = side === 'a' ? turn.response_a : turn.response_b;
                                    const model = side === 'a' ? turn.model_a : turn.model_b;
                                    const isSelected = turn.selected_side === side;
                                    const sideEvaluations = turn.evaluations.filter(
                                        (e) => e.response_side === side,
                                    );

                                    return (
                                        <div
                                            key={side}
                                            className={`rounded-xl border p-4 text-sm ${
                                                isSelected
                                                    ? 'border-emerald-300 bg-emerald-50/40'
                                                    : 'border-gray-200 bg-gray-50/60'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between">
                                                <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                    Response {side.toUpperCase()} · {model}
                                                </p>
                                            </div>
                                            <p className="mt-2 whitespace-pre-wrap text-gray-900">
                                                {text ?? '—'}
                                            </p>

                                            {sideEvaluations.length > 0 && (
                                                <ul className="mt-3 space-y-1">
                                                    {sideEvaluations.map((evaluation) => {
                                                        const criterion = criterionById.get(
                                                            evaluation.criterion_id,
                                                        );
                                                        return (
                                                            <li
                                                                key={`${side}-${evaluation.criterion_id}`}
                                                                className="text-xs text-gray-700"
                                                            >
                                                                <span className="font-semibold">
                                                                    {criterion?.name ?? 'Criterion'}:
                                                                </span>{' '}
                                                                {evaluation.rating_value}
                                                                {evaluation.justification && (
                                                                    <span className="ml-1 italic text-gray-500">
                                                                        — {evaluation.justification}
                                                                    </span>
                                                                )}
                                                            </li>
                                                        );
                                                    })}
                                                </ul>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>

                            {turn.sxs_rating !== null && (
                                <section className="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-3">
                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        SxS rating: {turn.sxs_rating}
                                    </p>
                                    {turn.sxs_justification && (
                                        <p className="mt-1 text-xs text-gray-700">
                                            {turn.sxs_justification}
                                        </p>
                                    )}
                                </section>
                            )}

                            {turn.selected_response_rewrite && (
                                <section className="mt-4">
                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Candidate rewrite
                                    </p>
                                    <p className="mt-1 whitespace-pre-wrap rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-900">
                                        {turn.selected_response_rewrite}
                                    </p>
                                </section>
                            )}

                            {turn.form_responses.length > 0 && (
                                <section className="mt-4">
                                    <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Form responses
                                    </p>
                                    <ul className="mt-1 space-y-1 text-xs text-gray-700">
                                        {turn.form_responses.map((fr) => (
                                            <li key={`${fr.stage}-${fr.field_key}`}>
                                                <span className="font-semibold">
                                                    {fr.stage} · {fr.field_key}:
                                                </span>{' '}
                                                {fr.value}
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            )}
                        </article>
                    ))}
                </div>

                <aside className="sticky top-20 h-fit space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div>
                        <p className="text-[11px] font-semibold uppercase tracking-[0.28em] text-gray-500">
                            Jump to turn
                        </p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {turns.map((turn) => (
                                <a
                                    key={turn.id}
                                    href={`#turn-${turn.turn_number}`}
                                    className="rounded-lg border border-gray-200 bg-white px-2 py-1 text-[11px] font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    T{turn.turn_number}
                                </a>
                            ))}
                        </div>
                    </div>

                    <div className="border-t border-gray-100 pt-4">
                        <label className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Overall score (0–100)
                        </label>
                        <input
                            type="number"
                            min={0}
                            max={100}
                            step={0.01}
                            value={score}
                            onChange={(e) => setScore(parseFloat(e.target.value) || 0)}
                            disabled={locked || !permissions.can_score}
                            className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50"
                        />
                    </div>

                    <div>
                        <label className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Decision
                        </label>
                        <select
                            value={decision}
                            onChange={(e) => setDecision(e.target.value)}
                            disabled={locked || !permissions.can_score}
                            className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50"
                        >
                            {DECISION_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            Comments
                        </label>
                        <textarea
                            value={comments}
                            onChange={(e) => setComments(e.target.value)}
                            disabled={locked || !permissions.can_score}
                            rows={4}
                            className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-50"
                        />
                    </div>

                    {feedback && (
                        <p className="rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                            {feedback}
                        </p>
                    )}
                    {error && (
                        <p className="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{error}</p>
                    )}

                    <div className="flex gap-2 border-t border-gray-100 pt-4">
                        <button
                            type="button"
                            disabled={locked || !permissions.can_score || saving}
                            onClick={() => {
                                void saveDraft();
                            }}
                            className="flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {saving ? 'Saving…' : 'Save draft'}
                        </button>
                        <button
                            type="button"
                            disabled={locked || !permissions.can_finalize || finalizing}
                            onClick={() => {
                                void finalize();
                            }}
                            className="flex-1 rounded-xl bg-gray-950 px-3 py-2 text-xs font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {finalizing ? 'Finalizing…' : 'Finalize'}
                        </button>
                    </div>

                    {locked && (
                        <p className="rounded-lg bg-gray-50 px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                            Finalized · locked
                        </p>
                    )}

                    <button
                        type="button"
                        onClick={() => router.visit(`/admin/results/attempt/${attempt.id}`)}
                        className="w-full text-center text-[11px] font-semibold text-gray-500 hover:text-gray-700"
                    >
                        Back to attempt
                    </button>
                </aside>
            </div>
        </AdminLayout>
    );
}
