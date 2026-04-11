import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

type TestResult = {
    id: number;
    test_case_id: number;
    test_case: {
        input: string | null;
        expected_output: string | null;
        is_hidden: boolean;
    };
    passed: boolean;
    actual_output: string | null;
    runtime_ms: number | null;
    memory_kb: number | null;
    error: string | null;
};

type Props = {
    answer: {
        id: number;
        status: string;
        auto_score: number | null;
        reviewer_score: number | null;
        question_stem: string;
        question_points: number;
    };
    attempt: { id: number };
    quiz: { id: number; title: string };
    candidate: { name: string | null; email: string | null };
    submission: {
        id: number;
        language: string;
        code: string;
        submitted_at: string | null;
    } | null;
    test_results: TestResult[];
    permissions: {
        can_rerun: boolean;
        can_override: boolean;
    };
};

export default function CodingReview({
    answer,
    attempt,
    quiz,
    candidate,
    submission,
    test_results,
    permissions,
}: Props) {
    const [rerunning, setRerunning] = useState(false);
    const [overriding, setOverriding] = useState(false);
    const [overrideScore, setOverrideScore] = useState<number>(answer.reviewer_score ?? 0);
    const [overrideReason, setOverrideReason] = useState('');
    const [feedback, setFeedback] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const totalTests = test_results.length;
    const passedTests = test_results.filter((r) => r.passed).length;

    const doRerun = async () => {
        if (rerunning) return;
        setRerunning(true);
        setFeedback(null);
        setError(null);
        try {
            await axios.post(`/admin/coding/review/${answer.id}/rerun`);
            setFeedback('Re-run dispatched. Refresh in a moment to see updated results.');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Failed to dispatch re-run.');
        } finally {
            setRerunning(false);
        }
    };

    const doOverride = async () => {
        if (overriding) return;
        if (overrideReason.trim().length < 3) {
            setError('Reason is required and must be at least 3 characters.');
            return;
        }
        setOverriding(true);
        setFeedback(null);
        setError(null);
        try {
            await axios.post(`/admin/coding/review/${answer.id}/override`, {
                reviewer_score: overrideScore,
                reason: overrideReason.trim(),
            });
            setFeedback('Score overridden.');
            setOverrideReason('');
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Override failed.');
        } finally {
            setOverriding(false);
        }
    };

    return (
        <AdminLayout>
            <Head title={`Code review — ${candidate.name ?? quiz.title}`} />

            <div className="mx-auto max-w-6xl">
                <Link
                    href={`/admin/results/attempt/${attempt.id}`}
                    className="text-[11px] font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-700"
                >
                    ← Attempt #{attempt.id}
                </Link>

                <header className="mt-3 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.28em] text-gray-500">
                        Coding review
                    </p>
                    <h1 className="mt-1 text-xl font-semibold text-gray-950">{answer.question_stem}</h1>
                    <p className="mt-2 text-xs text-gray-600">
                        {candidate.name ?? 'Candidate'} · {candidate.email} · {quiz.title} ·{' '}
                        {answer.question_points} pts
                    </p>

                    <dl className="mt-4 grid grid-cols-3 gap-3 text-xs">
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">Language</dt>
                            <dd className="mt-0.5 inline-block rounded-full bg-gray-100 px-2 py-0.5 font-semibold text-gray-800">
                                {submission?.language ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">Auto score</dt>
                            <dd className="mt-0.5 font-semibold text-gray-900">
                                {answer.auto_score !== null ? `${answer.auto_score.toFixed(1)} pts` : '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">Tests</dt>
                            <dd className="mt-0.5 font-semibold text-gray-900">
                                {passedTests} / {totalTests} passing
                            </dd>
                        </div>
                    </dl>
                </header>

                <section className="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-sm font-semibold text-gray-950">Submission</h2>
                        {submission?.submitted_at && (
                            <span className="text-[11px] text-gray-500">
                                {new Date(submission.submitted_at).toLocaleString()}
                            </span>
                        )}
                    </div>
                    <pre className="max-h-[480px] overflow-auto rounded-xl border border-gray-200 bg-gray-950 p-4 text-xs leading-relaxed text-gray-100">
                        <code>{submission?.code ?? '// no submission'}</code>
                    </pre>
                </section>

                <section className="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div className="mb-3 flex items-center justify-between">
                        <h2 className="text-sm font-semibold text-gray-950">Test results</h2>
                        {permissions.can_rerun && (
                            <button
                                type="button"
                                onClick={() => {
                                    void doRerun();
                                }}
                                disabled={rerunning}
                                className="rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {rerunning ? 'Dispatching…' : 'Re-run tests'}
                            </button>
                        )}
                    </div>

                    {test_results.length === 0 ? (
                        <p className="py-8 text-center text-sm text-gray-500">No test results yet.</p>
                    ) : (
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Case
                                    </th>
                                    <th className="px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Status
                                    </th>
                                    <th className="px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Expected
                                    </th>
                                    <th className="px-3 py-2 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Actual
                                    </th>
                                    <th className="px-3 py-2 text-right text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                        Runtime
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {test_results.map((result, index) => (
                                    <tr key={result.id} className="align-top">
                                        <td className="px-3 py-2 text-xs text-gray-700">
                                            #{index + 1}
                                            {result.test_case.is_hidden && (
                                                <span className="ml-1 rounded bg-amber-50 px-1 text-[9px] font-semibold uppercase text-amber-700">
                                                    hidden
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-3 py-2">
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                                                    result.passed
                                                        ? 'bg-emerald-50 text-emerald-700'
                                                        : 'bg-red-50 text-red-700'
                                                }`}
                                            >
                                                {result.passed ? 'PASS' : 'FAIL'}
                                            </span>
                                        </td>
                                        <td className="px-3 py-2 font-mono text-[11px] text-gray-700">
                                            {result.test_case.expected_output ?? '—'}
                                        </td>
                                        <td className="px-3 py-2 font-mono text-[11px] text-gray-700">
                                            {result.actual_output ?? '—'}
                                            {result.error && (
                                                <div className="mt-1 text-red-600">{result.error}</div>
                                            )}
                                        </td>
                                        <td className="px-3 py-2 text-right text-[11px] text-gray-600">
                                            {result.runtime_ms !== null ? `${result.runtime_ms}ms` : '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </section>

                {permissions.can_override && (
                    <section className="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 className="text-sm font-semibold text-gray-950">Score override</h2>
                        <p className="mt-1 text-xs text-gray-600">
                            Overrides are audit-logged. Provide a reason so the decision is traceable.
                        </p>
                        <div className="mt-3 grid gap-3 md:grid-cols-[140px,1fr]">
                            <div>
                                <label className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                    Reviewer score
                                </label>
                                <input
                                    type="number"
                                    min={0}
                                    step={0.01}
                                    value={overrideScore}
                                    onChange={(e) => setOverrideScore(parseFloat(e.target.value) || 0)}
                                    className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none"
                                />
                            </div>
                            <div>
                                <label className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                    Reason
                                </label>
                                <textarea
                                    rows={3}
                                    value={overrideReason}
                                    onChange={(e) => setOverrideReason(e.target.value)}
                                    className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none"
                                />
                            </div>
                        </div>
                        <div className="mt-3 flex justify-end">
                            <button
                                type="button"
                                onClick={() => {
                                    void doOverride();
                                }}
                                disabled={overriding}
                                className="rounded-xl bg-gray-950 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {overriding ? 'Saving…' : 'Apply override'}
                            </button>
                        </div>
                    </section>
                )}

                {feedback && (
                    <p className="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                        {feedback}
                    </p>
                )}
                {error && <p className="mt-4 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">{error}</p>}

                <div className="mt-6">
                    <button
                        type="button"
                        onClick={() => router.reload()}
                        className="text-[11px] font-semibold text-gray-500 hover:text-gray-700"
                    >
                        Refresh data
                    </button>
                </div>
            </div>
        </AdminLayout>
    );
}
