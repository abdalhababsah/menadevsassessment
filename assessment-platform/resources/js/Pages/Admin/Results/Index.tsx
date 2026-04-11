import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

type QuizRow = {
    id: number;
    title: string;
    status: string;
    attempts_count: number;
};

type Props = {
    quizzes: QuizRow[];
};

export default function ResultsIndex({ quizzes }: Props) {
    return (
        <AdminLayout>
            <Head title="Results" />

            <div className="mx-auto max-w-6xl">
                <header className="mb-6">
                    <h1 className="text-2xl font-semibold text-gray-950">Results</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        Pick a quiz to see ranked attempts, scores, and proctoring flags.
                    </p>
                </header>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Quiz
                                </th>
                                <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Submitted attempts
                                </th>
                                <th className="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {quizzes.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No quizzes yet.
                                    </td>
                                </tr>
                            ) : (
                                quizzes.map((quiz) => (
                                    <tr key={quiz.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                            {quiz.title}
                                        </td>
                                        <td className="px-4 py-3 text-sm text-gray-600">
                                            {quiz.status}
                                        </td>
                                        <td className="px-4 py-3 text-right text-sm text-gray-900">
                                            {quiz.attempts_count}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={`/admin/results/${quiz.id}`}
                                                className="text-xs font-semibold text-gray-900 underline underline-offset-2 hover:text-gray-700"
                                            >
                                                View results →
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
