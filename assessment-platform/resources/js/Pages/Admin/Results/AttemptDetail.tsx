import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

type Answer = {
    id: number;
    question: {
        id: number;
        type: string;
        stem: string;
        points: number;
    } | null;
    status: string;
    auto_score: number | null;
    reviewer_score: number | null;
    time_spent_seconds: number | null;
    selected_option_ids: number[];
    has_coding_submission: boolean;
    has_rlhf_turns: boolean;
    rlhf_review: {
        score: number;
        decision: string;
        finalized: boolean;
    } | null;
    drill_down_url: string | null;
};

type SuspiciousEvent = {
    id: number;
    event_type: string;
    occurred_at: string | null;
    metadata: Record<string, unknown> | null;
};

type Snapshot = {
    id: number;
    url: string;
    captured_at: string | null;
    flagged: boolean;
};

type Props = {
    attempt: {
        id: number;
        status: string;
        rlhf_review_status: string;
        auto_score: number | null;
        final_score: number | null;
        started_at: string | null;
        submitted_at: string | null;
        time_taken_seconds: number | null;
    };
    quiz: { id: number; title: string };
    candidate: { id: number | null; name: string | null; email: string | null };
    answers: Answer[];
    suspicious_events: SuspiciousEvent[];
    snapshots: Snapshot[];
    permissions: {
        view_suspicious: boolean;
        view_snapshots: boolean;
    };
};

type Tab = 'answers' | 'suspicious' | 'snapshots';

function formatScore(score: number | null): string {
    return score === null ? '—' : `${score.toFixed(1)}%`;
}

export default function AttemptDetail({
    attempt,
    quiz,
    candidate,
    answers,
    suspicious_events,
    snapshots,
    permissions,
}: Props) {
    const [tab, setTab] = useState<Tab>('answers');

    const tabs: { key: Tab; label: string; count?: number; visible: boolean }[] = [
        { key: 'answers', label: 'Answers', count: answers.length, visible: true },
        {
            key: 'suspicious',
            label: 'Suspicious events',
            count: suspicious_events.length,
            visible: permissions.view_suspicious,
        },
        {
            key: 'snapshots',
            label: 'Snapshots',
            count: snapshots.length,
            visible: permissions.view_snapshots,
        },
    ];

    return (
        <AdminLayout>
            <Head title={`Attempt #${attempt.id} — ${quiz.title}`} />

            <div className="mx-auto max-w-5xl">
                <Link
                    href={`/admin/results/${quiz.id}`}
                    className="text-[11px] font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-700"
                >
                    ← {quiz.title}
                </Link>

                <header className="mt-3 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-gray-950">
                                {candidate.name ?? 'Candidate'}
                            </h1>
                            <p className="mt-1 text-sm text-gray-600">{candidate.email}</p>
                        </div>
                        <div className="text-right">
                            <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                Final score
                            </p>
                            <p className="mt-1 text-2xl font-semibold text-gray-950">
                                {formatScore(attempt.final_score)}
                            </p>
                            <p className="text-xs text-gray-500">
                                auto {formatScore(attempt.auto_score)}
                            </p>
                        </div>
                    </div>

                    <dl className="mt-4 grid grid-cols-3 gap-3 text-xs">
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">Status</dt>
                            <dd className="mt-0.5 font-semibold text-gray-900">{attempt.status}</dd>
                        </div>
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">RLHF review</dt>
                            <dd className="mt-0.5 font-semibold text-gray-900">{attempt.rlhf_review_status}</dd>
                        </div>
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">Time taken</dt>
                            <dd className="mt-0.5 font-semibold text-gray-900">
                                {attempt.time_taken_seconds !== null
                                    ? `${Math.floor(attempt.time_taken_seconds / 60)}m ${attempt.time_taken_seconds % 60}s`
                                    : '—'}
                            </dd>
                        </div>
                    </dl>
                </header>

                <nav className="mt-6 flex gap-2 border-b border-gray-200">
                    {tabs
                        .filter((t) => t.visible)
                        .map((t) => (
                            <button
                                key={t.key}
                                type="button"
                                onClick={() => setTab(t.key)}
                                className={`relative -mb-px border-b-2 px-4 py-2 text-sm font-semibold transition ${
                                    tab === t.key
                                        ? 'border-gray-950 text-gray-950'
                                        : 'border-transparent text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                {t.label}
                                {typeof t.count === 'number' && (
                                    <span className="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-700">
                                        {t.count}
                                    </span>
                                )}
                            </button>
                        ))}
                </nav>

                <section className="mt-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    {tab === 'answers' && (
                        <ul className="divide-y divide-gray-100">
                            {answers.map((answer) => (
                                <li key={answer.id} className="py-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                                {answer.question?.type ?? 'unknown'} ·{' '}
                                                {answer.question?.points.toFixed(0)} pts
                                            </p>
                                            <p className="mt-1 text-sm text-gray-900">{answer.question?.stem}</p>
                                        </div>
                                        <div className="shrink-0 text-right">
                                            <p className="text-[10px] uppercase tracking-wide text-gray-500">
                                                {answer.status}
                                            </p>
                                            <p className="mt-0.5 text-sm font-semibold text-gray-900">
                                                {answer.auto_score !== null
                                                    ? `${answer.auto_score.toFixed(1)} pts`
                                                    : 'unscored'}
                                            </p>
                                        </div>
                                    </div>
                                    {answer.drill_down_url && (
                                        <Link
                                            href={answer.drill_down_url}
                                            className="mt-2 inline-block text-xs font-semibold text-gray-900 underline underline-offset-2 hover:text-gray-700"
                                        >
                                            Open review →
                                        </Link>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}

                    {tab === 'suspicious' && permissions.view_suspicious && (
                        <ol className="divide-y divide-gray-100">
                            {suspicious_events.length === 0 ? (
                                <li className="py-6 text-center text-sm text-gray-500">
                                    No proctoring events recorded.
                                </li>
                            ) : (
                                suspicious_events.map((event) => (
                                    <li key={event.id} className="py-3">
                                        <p className="text-sm font-semibold text-gray-900">
                                            {event.event_type.replace(/_/g, ' ')}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            {event.occurred_at
                                                ? new Date(event.occurred_at).toLocaleString()
                                                : ''}
                                        </p>
                                    </li>
                                ))
                            )}
                        </ol>
                    )}

                    {tab === 'snapshots' && permissions.view_snapshots && (
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-3">
                            {snapshots.length === 0 ? (
                                <p className="col-span-full py-6 text-center text-sm text-gray-500">
                                    No camera snapshots captured.
                                </p>
                            ) : (
                                snapshots.map((snapshot) => (
                                    <div
                                        key={snapshot.id}
                                        className="rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700"
                                    >
                                        <div className="font-mono text-[10px] text-gray-500">{snapshot.url}</div>
                                        <div className="mt-1">
                                            {snapshot.captured_at
                                                ? new Date(snapshot.captured_at).toLocaleString()
                                                : ''}
                                        </div>
                                        {snapshot.flagged && (
                                            <div className="mt-1 text-[10px] font-semibold uppercase text-red-600">
                                                Flagged
                                            </div>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>
                    )}
                </section>
            </div>
        </AdminLayout>
    );
}
