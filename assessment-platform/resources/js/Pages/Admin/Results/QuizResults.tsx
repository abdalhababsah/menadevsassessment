import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

type Attempt = {
    rank: number;
    id: number;
    candidate: {
        id: number | null;
        name: string | null;
        email: string | null;
    };
    status: string;
    rlhf_review_status: string;
    auto_score: number | null;
    final_score: number | null;
    started_at: string | null;
    submitted_at: string | null;
    time_taken_seconds: number | null;
    suspicious_events_count: number | null;
    camera_snapshots_count: number | null;
};

type Props = {
    quiz: {
        id: number;
        title: string;
        passing_score: number | null;
    };
    attempts: Attempt[];
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    permissions: {
        view_suspicious: boolean;
        view_snapshots: boolean;
        export: boolean;
    };
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
};

function formatTime(seconds: number | null): string {
    if (seconds === null) {
        return '—';
    }
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}m ${s}s`;
}

function formatScore(score: number | null): string {
    return score === null ? '—' : `${score.toFixed(1)}%`;
}

function suspiciousBadgeColor(count: number): string {
    if (count === 0) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    if (count < 3) return 'bg-amber-50 text-amber-700 border-amber-200';
    if (count < 6) return 'bg-orange-50 text-orange-700 border-orange-200';
    return 'bg-red-50 text-red-700 border-red-200';
}

function rlhfBadgeColor(status: string): string {
    switch (status) {
        case 'completed':
            return 'bg-emerald-50 text-emerald-700';
        case 'pending':
            return 'bg-amber-50 text-amber-700';
        default:
            return 'bg-gray-100 text-gray-600';
    }
}

export default function QuizResults({ quiz, attempts, pagination, permissions }: Props) {
    const [panelAttempt, setPanelAttempt] = useState<Attempt | null>(null);
    const [panelEvents, setPanelEvents] = useState<SuspiciousEvent[]>([]);
    const [panelSnapshots, setPanelSnapshots] = useState<Snapshot[]>([]);
    const [panelLoading, setPanelLoading] = useState(false);

    const openFlagPanel = async (attempt: Attempt) => {
        setPanelAttempt(attempt);
        setPanelLoading(true);
        setPanelEvents([]);
        setPanelSnapshots([]);
        try {
            const { data } = await axios.get(`/admin/results/attempt/${attempt.id}`, {
                headers: { 'X-Inertia': 'true', 'X-Inertia-Version': '' },
            });
            const props = data?.props ?? {};
            setPanelEvents(props.suspicious_events ?? []);
            setPanelSnapshots(props.snapshots ?? []);
        } catch {
            // Silent — panel just stays empty.
        } finally {
            setPanelLoading(false);
        }
    };

    const closePanel = () => setPanelAttempt(null);

    return (
        <AdminLayout>
            <Head title={`Results — ${quiz.title}`} />

            <div className="mx-auto max-w-7xl">
                <header className="mb-6 flex items-center justify-between">
                    <div>
                        <Link
                            href="/admin/results"
                            className="text-[11px] font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-700"
                        >
                            ← Results
                        </Link>
                        <h1 className="mt-1 text-2xl font-semibold text-gray-950">{quiz.title}</h1>
                        <p className="mt-1 text-sm text-gray-600">
                            {pagination.total} submitted attempt{pagination.total === 1 ? '' : 's'}
                            {quiz.passing_score !== null && (
                                <> · Passing score: {quiz.passing_score.toFixed(0)}%</>
                            )}
                        </p>
                    </div>
                    {permissions.export && (
                        <a
                            href={`/admin/results/${quiz.id}/export`}
                            className="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Export CSV
                        </a>
                    )}
                </header>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Rank', 'Candidate', 'Auto', 'Final', 'Time', 'Submitted', 'RLHF', 'Flags', ''].map(
                                    (label) => (
                                        <th
                                            key={label}
                                            className="px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                                        >
                                            {label}
                                        </th>
                                    ),
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {attempts.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={9}
                                        className="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No submitted attempts yet.
                                    </td>
                                </tr>
                            ) : (
                                attempts.map((attempt) => (
                                    <tr key={attempt.id} className="hover:bg-gray-50">
                                        <td className="px-3 py-3 text-sm font-semibold text-gray-900">
                                            #{attempt.rank}
                                        </td>
                                        <td className="px-3 py-3 text-sm text-gray-900">
                                            <div className="font-medium">{attempt.candidate.name ?? '—'}</div>
                                            <div className="text-xs text-gray-500">{attempt.candidate.email}</div>
                                        </td>
                                        <td className="px-3 py-3 text-sm text-gray-700">
                                            {formatScore(attempt.auto_score)}
                                        </td>
                                        <td className="px-3 py-3 text-sm font-semibold text-gray-900">
                                            {formatScore(attempt.final_score)}
                                        </td>
                                        <td className="px-3 py-3 text-sm text-gray-700">
                                            {formatTime(attempt.time_taken_seconds)}
                                        </td>
                                        <td className="px-3 py-3 text-xs text-gray-600">
                                            {attempt.submitted_at
                                                ? new Date(attempt.submitted_at).toLocaleString()
                                                : '—'}
                                        </td>
                                        <td className="px-3 py-3 text-xs">
                                            <span
                                                className={`rounded-full px-2 py-1 font-semibold ${rlhfBadgeColor(attempt.rlhf_review_status)}`}
                                            >
                                                {attempt.rlhf_review_status}
                                            </span>
                                        </td>
                                        <td className="px-3 py-3 text-xs">
                                            {permissions.view_suspicious &&
                                            attempt.suspicious_events_count !== null ? (
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        void openFlagPanel(attempt);
                                                    }}
                                                    className={`rounded-full border px-2 py-1 font-semibold ${suspiciousBadgeColor(attempt.suspicious_events_count)}`}
                                                >
                                                    ⚠ {attempt.suspicious_events_count}
                                                </button>
                                            ) : (
                                                <span className="text-gray-400">—</span>
                                            )}
                                        </td>
                                        <td className="px-3 py-3 text-right">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    router.visit(`/admin/results/attempt/${attempt.id}`)
                                                }
                                                className="text-xs font-semibold text-gray-900 underline underline-offset-2 hover:text-gray-700"
                                            >
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {pagination.last_page > 1 && (
                    <div className="mt-4 flex items-center justify-between text-xs text-gray-600">
                        <span>
                            Page {pagination.current_page} of {pagination.last_page}
                        </span>
                        <div className="flex gap-2">
                            {pagination.current_page > 1 && (
                                <Link
                                    href={`/admin/results/${quiz.id}?page=${pagination.current_page - 1}`}
                                    className="rounded-lg border border-gray-200 bg-white px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                            )}
                            {pagination.current_page < pagination.last_page && (
                                <Link
                                    href={`/admin/results/${quiz.id}?page=${pagination.current_page + 1}`}
                                    className="rounded-lg border border-gray-200 bg-white px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {panelAttempt && (
                <div className="fixed inset-0 z-40 flex justify-end bg-black/30">
                    <div className="h-full w-full max-w-md overflow-y-auto bg-white p-6 shadow-xl">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Proctoring flags
                                </p>
                                <h2 className="mt-1 text-base font-semibold text-gray-950">
                                    {panelAttempt.candidate.name ?? 'Candidate'}
                                </h2>
                            </div>
                            <button
                                type="button"
                                onClick={closePanel}
                                className="text-xs font-semibold text-gray-500 hover:text-gray-700"
                            >
                                Close
                            </button>
                        </div>

                        {panelLoading ? (
                            <p className="mt-6 text-sm text-gray-500">Loading…</p>
                        ) : (
                            <>
                                <section className="mt-6">
                                    <h3 className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                        Events ({panelEvents.length})
                                    </h3>
                                    <ul className="mt-2 divide-y divide-gray-100 rounded-xl border border-gray-200">
                                        {panelEvents.length === 0 ? (
                                            <li className="px-3 py-4 text-xs text-gray-500">
                                                No proctoring events recorded.
                                            </li>
                                        ) : (
                                            panelEvents.map((event) => (
                                                <li key={event.id} className="px-3 py-2 text-xs">
                                                    <div className="font-semibold text-gray-900">
                                                        {event.event_type.replace(/_/g, ' ')}
                                                    </div>
                                                    <div className="text-gray-500">
                                                        {event.occurred_at
                                                            ? new Date(event.occurred_at).toLocaleString()
                                                            : ''}
                                                    </div>
                                                </li>
                                            ))
                                        )}
                                    </ul>
                                </section>

                                {permissions.view_snapshots && (
                                    <section className="mt-6">
                                        <h3 className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            Snapshots ({panelSnapshots.length})
                                        </h3>
                                        {panelSnapshots.length === 0 ? (
                                            <p className="mt-2 text-xs text-gray-500">No snapshots captured.</p>
                                        ) : (
                                            <div className="mt-2 grid grid-cols-3 gap-2">
                                                {panelSnapshots.map((snapshot) => (
                                                    <div
                                                        key={snapshot.id}
                                                        className="rounded border border-gray-200 bg-gray-50 p-2 text-[10px] text-gray-600"
                                                    >
                                                        {snapshot.captured_at
                                                            ? new Date(snapshot.captured_at).toLocaleTimeString()
                                                            : snapshot.url}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </section>
                                )}
                            </>
                        )}
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
