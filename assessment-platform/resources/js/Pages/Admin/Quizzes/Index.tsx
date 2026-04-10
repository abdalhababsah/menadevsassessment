import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

interface QuizRow {
    id: number;
    title: string;
    status: string;
    status_label: string;
    sections_count: number;
    questions_count: number;
    invitations_count: number;
    created_at: string;
}

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    published: 'bg-green-100 text-green-800',
    archived: 'bg-amber-100 text-amber-800',
};

export default function Index({ quizzes }: { quizzes: QuizRow[] }) {
    const handlePublish = (quiz: QuizRow) => {
        router.post(route('admin.quizzes.publish', quiz.id));
    };

    const handleUnpublish = (quiz: QuizRow) => {
        router.post(route('admin.quizzes.unpublish', quiz.id));
    };

    const handleDuplicate = (quiz: QuizRow) => {
        router.post(route('admin.quizzes.duplicate', quiz.id));
    };

    const handleDelete = (quiz: QuizRow) => {
        if (!confirm(`Delete "${quiz.title}"? This cannot be undone.`)) {
            return;
        }
        router.delete(route('admin.quizzes.destroy', quiz.id));
    };

    return (
        <AdminLayout>
            <Head title="Quizzes" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">Quizzes</h2>
                    <p className="mt-1 text-sm text-gray-600">{quizzes.length} quizzes</p>
                </div>
                <Link
                    href={route('admin.quizzes.create')}
                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    Create Quiz
                </Link>
            </div>

            <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Title</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sections</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Questions</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Invitations</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created</th>
                            <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {quizzes.length === 0 ? (
                            <tr>
                                <td colSpan={7} className="px-6 py-12 text-center text-sm text-gray-500">
                                    No quizzes yet.
                                </td>
                            </tr>
                        ) : (
                            quizzes.map((quiz) => (
                                <tr key={quiz.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{quiz.title}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${STATUS_STYLES[quiz.status] ?? 'bg-gray-100'}`}>
                                            {quiz.status_label}
                                        </span>
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{quiz.sections_count}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{quiz.questions_count}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{quiz.invitations_count}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{quiz.created_at}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <Link href={route('admin.quizzes.edit', quiz.id)} className="text-indigo-600 hover:text-indigo-900">
                                            Edit
                                        </Link>
                                        {quiz.status === 'draft' ? (
                                            <button onClick={() => handlePublish(quiz)} className="ml-3 text-green-600 hover:text-green-900">
                                                Publish
                                            </button>
                                        ) : (
                                            <button onClick={() => handleUnpublish(quiz)} className="ml-3 text-amber-600 hover:text-amber-900">
                                                Unpublish
                                            </button>
                                        )}
                                        <button onClick={() => handleDuplicate(quiz)} className="ml-3 text-gray-600 hover:text-gray-900">
                                            Duplicate
                                        </button>
                                        <button onClick={() => handleDelete(quiz)} className="ml-3 text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
