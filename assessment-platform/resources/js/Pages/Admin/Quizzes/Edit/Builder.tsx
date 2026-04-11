import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState, FormEventHandler } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

interface QuestionPreview {
    type: string;
    type_label: string;
    stem: string;
    difficulty: string;
    points: number;
    version: number;
}

interface SectionQuestion {
    id: number;
    question_id: number;
    question_version: number;
    points_override: number | null;
    time_limit_override_seconds: number | null;
    position: number;
    question: QuestionPreview | null;
}

interface Section {
    id: number;
    title: string;
    description: string | null;
    time_limit_seconds: number | null;
    position: number;
    questions: SectionQuestion[];
}

interface QuizSummary {
    id: number;
    title: string;
    status: string;
}

interface BankResult {
    id: number;
    type: string;
    type_label: string;
    difficulty: string;
    stem: string;
    points: number;
    version: number;
    tags: string[];
}

const TYPE_BADGE: Record<string, string> = {
    single_select: 'bg-blue-100 text-blue-800',
    multi_select: 'bg-indigo-100 text-indigo-800',
    coding: 'bg-emerald-100 text-emerald-800',
    rlhf: 'bg-purple-100 text-purple-800',
};

export default function Builder({
    quiz,
    sections,
}: {
    quiz: QuizSummary;
    sections: Section[];
}) {
    const [activeSectionId, setActiveSectionId] = useState<number | null>(sections[0]?.id ?? null);
    const [editingSection, setEditingSection] = useState<Section | null>(null);
    const [showAddSection, setShowAddSection] = useState(false);
    const [showAddQuestion, setShowAddQuestion] = useState(false);

    const activeSection = sections.find((s) => s.id === activeSectionId) ?? null;

    const moveSection = (sectionId: number, direction: -1 | 1) => {
        const ordered = [...sections];
        const index = ordered.findIndex((s) => s.id === sectionId);
        if (index === -1) return;
        const target = index + direction;
        if (target < 0 || target >= ordered.length) return;
        [ordered[index], ordered[target]] = [ordered[target], ordered[index]];
        router.post(route('admin.quizzes.sections.reorder', quiz.id), {
            section_ids: ordered.map((s) => s.id),
        }, { preserveScroll: true });
    };

    const moveQuestion = (sectionId: number, sectionQuestionId: number, direction: -1 | 1) => {
        const section = sections.find((s) => s.id === sectionId);
        if (!section) return;
        const ordered = [...section.questions];
        const index = ordered.findIndex((q) => q.id === sectionQuestionId);
        if (index === -1) return;
        const target = index + direction;
        if (target < 0 || target >= ordered.length) return;
        [ordered[index], ordered[target]] = [ordered[target], ordered[index]];
        router.post(route('admin.quizzes.sections.questions.reorder', [quiz.id, sectionId]), {
            section_question_ids: ordered.map((q) => q.id),
        }, { preserveScroll: true });
    };

    const deleteSection = (sectionId: number) => {
        router.delete(route('admin.quizzes.sections.destroy', [quiz.id, sectionId]), {
            preserveScroll: true,
        });
    };

    const detachQuestion = (sectionId: number, sectionQuestionId: number) => {
        router.delete(route('admin.quizzes.sections.questions.detach', [quiz.id, sectionId, sectionQuestionId]), {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout>
            <Head title={`Builder: ${quiz.title}`} />

            <div className="mb-6">
                <Link href={route('admin.quizzes.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Quizzes
                </Link>
                <div className="mt-2 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{quiz.title}</h1>
                        <p className="text-sm text-gray-600">Quiz Builder</p>
                    </div>
                </div>

                <div className="mt-4 flex gap-1 border-b border-gray-200 text-sm">
                    <Link
                        href={route('admin.quizzes.edit', quiz.id)}
                        className="border-b-2 border-transparent px-3 py-2 text-gray-500 hover:text-gray-700"
                    >
                        Settings
                    </Link>
                    <Link
                        href={route('admin.quizzes.builder', quiz.id)}
                        className="border-b-2 border-indigo-600 px-3 py-2 font-medium text-indigo-600"
                    >
                        Builder
                    </Link>
                    <Link
                        href={route('admin.quizzes.invitations.index', quiz.id)}
                        className="border-b-2 border-transparent px-3 py-2 text-gray-500 hover:text-gray-700"
                    >
                        Invitations
                    </Link>
                </div>
            </div>

            <div className="grid grid-cols-12 gap-6">
                {/* Sections list */}
                <aside className="col-span-12 lg:col-span-5">
                    <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                        <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                            <h3 className="text-sm font-semibold text-gray-900">Sections</h3>
                            <button
                                onClick={() => setShowAddSection(true)}
                                className="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-500"
                            >
                                + Add Section
                            </button>
                        </div>
                        <ul className="divide-y divide-gray-200">
                            {sections.length === 0 && (
                                <li className="px-4 py-6 text-center text-sm text-gray-500">
                                    No sections yet. Add one to get started.
                                </li>
                            )}
                            {sections.map((section, index) => (
                                <li key={section.id}>
                                    <button
                                        onClick={() => setActiveSectionId(section.id)}
                                        className={`flex w-full items-start justify-between px-4 py-3 text-left transition ${
                                            activeSectionId === section.id ? 'bg-indigo-50' : 'hover:bg-gray-50'
                                        }`}
                                    >
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-900">{section.title}</p>
                                            <p className="mt-1 text-xs text-gray-500">
                                                {section.questions.length} {section.questions.length === 1 ? 'question' : 'questions'}
                                                {section.time_limit_seconds !== null && ` · ${section.time_limit_seconds}s limit`}
                                            </p>
                                        </div>
                                        <div className="ml-3 flex items-center gap-1">
                                            <button
                                                type="button"
                                                onClick={(e) => { e.stopPropagation(); moveSection(section.id, -1); }}
                                                disabled={index === 0}
                                                className="px-1 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                                                title="Move up"
                                            >↑</button>
                                            <button
                                                type="button"
                                                onClick={(e) => { e.stopPropagation(); moveSection(section.id, 1); }}
                                                disabled={index === sections.length - 1}
                                                className="px-1 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                                                title="Move down"
                                            >↓</button>
                                            <button
                                                type="button"
                                                onClick={(e) => { e.stopPropagation(); setEditingSection(section); }}
                                                className="px-1 text-indigo-600 hover:text-indigo-800 cursor-pointer"
                                                title="Edit"
                                            >✎</button>

                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <button
                                                        type="button"
                                                        onClick={(e) => e.stopPropagation()}
                                                        className="px-1 text-red-500 hover:text-red-700 cursor-pointer"
                                                        title="Delete"
                                                    >×</button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent onClick={(e) => e.stopPropagation()}>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete Section?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Are you sure you want to delete "{section.title}"? This will also remove all questions within this section.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction 
                                                            variant="destructive"
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                deleteSection(section.id);
                                                            }}
                                                        >
                                                            Delete Section
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>
                </aside>

                {/* Active section detail */}
                <section className="col-span-12 lg:col-span-7">
                    {activeSection === null ? (
                        <div className="flex h-64 items-center justify-center rounded-xl bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-200">
                            Select or create a section to start adding questions.
                        </div>
                    ) : (
                        <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                            <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                                <div>
                                    <h3 className="text-sm font-semibold text-gray-900">{activeSection.title}</h3>
                                    {activeSection.description && (
                                        <p className="mt-0.5 text-xs text-gray-500">{activeSection.description}</p>
                                    )}
                                </div>
                                <button
                                    onClick={() => setShowAddQuestion(true)}
                                    className="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-500"
                                >
                                    + Add Question
                                </button>
                            </div>
                            <ul className="divide-y divide-gray-200">
                                {activeSection.questions.length === 0 && (
                                    <li className="px-4 py-6 text-center text-sm text-gray-500">No questions yet.</li>
                                )}
                                {activeSection.questions.map((sq, index) => (
                                    <li key={sq.id} className="px-4 py-3">
                                        <div className="flex items-start gap-3">
                                            <div className="flex flex-col gap-0.5 pt-1">
                                                <button
                                                    type="button"
                                                    onClick={() => moveQuestion(activeSection.id, sq.id, -1)}
                                                    disabled={index === 0}
                                                    className="text-gray-400 hover:text-gray-700 disabled:opacity-30"
                                                >↑</button>
                                                <button
                                                    type="button"
                                                    onClick={() => moveQuestion(activeSection.id, sq.id, 1)}
                                                    disabled={index === activeSection.questions.length - 1}
                                                    className="text-gray-400 hover:text-gray-700 disabled:opacity-30"
                                                >↓</button>
                                            </div>
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    {sq.question && (
                                                        <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${TYPE_BADGE[sq.question.type] ?? 'bg-gray-100'}`}>
                                                            {sq.question.type_label}
                                                        </span>
                                                    )}
                                                    <span className="text-xs text-gray-500">v{sq.question_version}</span>
                                                </div>
                                                <p className="mt-1 text-sm text-gray-700">
                                                    {sq.question?.stem ?? <span className="italic text-gray-400">Question not found</span>}
                                                </p>
                                                <div className="mt-1 text-xs text-gray-500">
                                                    Points: {sq.points_override ?? sq.question?.points ?? '—'}
                                                    {sq.time_limit_override_seconds && ` · Time: ${sq.time_limit_override_seconds}s`}
                                                </div>
                                            </div>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <button
                                                        type="button"
                                                        className="text-red-500 hover:text-red-700 cursor-pointer"
                                                        title="Remove"
                                                    >×</button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Remove Question?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Are you sure you want to remove this question from the section? The question will remain in the library.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction 
                                                            variant="destructive"
                                                            onClick={() => detachQuestion(activeSection.id, sq.id)}
                                                        >
                                                            Remove Question
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </section>
            </div>

            {showAddSection && (
                <SectionFormModal
                    quizId={quiz.id}
                    onClose={() => setShowAddSection(false)}
                />
            )}

            {editingSection && (
                <SectionFormModal
                    quizId={quiz.id}
                    section={editingSection}
                    onClose={() => setEditingSection(null)}
                />
            )}

            {showAddQuestion && activeSection && (
                <AddQuestionModal
                    quizId={quiz.id}
                    sectionId={activeSection.id}
                    onClose={() => setShowAddQuestion(false)}
                />
            )}
        </AdminLayout>
    );
}

function SectionFormModal({
    quizId,
    section,
    onClose,
}: {
    quizId: number;
    section?: Section;
    onClose: () => void;
}) {
    const [title, setTitle] = useState(section?.title ?? '');
    const [description, setDescription] = useState(section?.description ?? '');
    const [timeLimitSeconds, setTimeLimitSeconds] = useState<number | null>(section?.time_limit_seconds ?? null);
    const [submitting, setSubmitting] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setSubmitting(true);
        const payload = { title, description, time_limit_seconds: timeLimitSeconds };

        if (section) {
            router.put(route('admin.quizzes.sections.update', [quizId, section.id]), payload, {
                preserveScroll: true,
                onSuccess: () => onClose(),
                onFinish: () => setSubmitting(false),
            });
        } else {
            router.post(route('admin.quizzes.sections.store', quizId), payload, {
                preserveScroll: true,
                onSuccess: () => onClose(),
                onFinish: () => setSubmitting(false),
            });
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <form onSubmit={submit} className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 className="text-lg font-semibold text-gray-900">{section ? 'Edit Section' : 'New Section'}</h3>
                <div className="mt-4 space-y-3">
                    <div>
                        <label className="block text-xs font-medium text-gray-700">Title</label>
                        <input
                            type="text"
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            required
                            autoFocus
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700">Description (optional)</label>
                        <textarea
                            value={description ?? ''}
                            onChange={(e) => setDescription(e.target.value)}
                            rows={2}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700">Time Limit (seconds, optional)</label>
                        <input
                            type="number"
                            value={timeLimitSeconds ?? ''}
                            onChange={(e) => setTimeLimitSeconds(e.target.value ? parseInt(e.target.value, 10) : null)}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            min={1}
                        />
                    </div>
                </div>
                <div className="mt-5 flex justify-end gap-2">
                    <button type="button" onClick={onClose} className="rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={submitting || !title}
                        className="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    );
}

function AddQuestionModal({
    quizId,
    sectionId,
    onClose,
}: {
    quizId: number;
    sectionId: number;
    onClose: () => void;
}) {
    const [tab, setTab] = useState<'bank' | 'create'>('bank');
    const [searchTerm, setSearchTerm] = useState('');
    const [results, setResults] = useState<BankResult[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const handle = setTimeout(() => {
            setLoading(true);
            fetch(route('admin.quizzes.bank-search', quizId) + '?q=' + encodeURIComponent(searchTerm), {
                headers: { Accept: 'application/json' },
            })
                .then((r) => r.json())
                .then((data: { questions: BankResult[] }) => setResults(data.questions))
                .finally(() => setLoading(false));
        }, 250);

        return () => clearTimeout(handle);
    }, [searchTerm, quizId]);

    const attach = (questionId: number) => {
        router.post(
            route('admin.quizzes.sections.questions.attach', [quizId, sectionId]),
            { question_id: questionId },
            {
                preserveScroll: true,
                onSuccess: () => onClose(),
            },
        );
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="w-full max-w-3xl rounded-xl bg-white p-6 shadow-xl">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-gray-900">Add Question</h3>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700">×</button>
                </div>

                <div className="mt-4 flex gap-1 border-b border-gray-200">
                    <button
                        onClick={() => setTab('bank')}
                        className={`px-4 py-2 text-sm font-medium ${
                            tab === 'bank' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'
                        }`}
                    >
                        Pick from bank
                    </button>
                    <button
                        onClick={() => setTab('create')}
                        className={`px-4 py-2 text-sm font-medium ${
                            tab === 'create' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'
                        }`}
                    >
                        Create new
                    </button>
                </div>

                {tab === 'bank' && (
                    <div className="mt-4">
                        <input
                            type="search"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Search questions..."
                            className="block w-full rounded-lg border-gray-300 text-sm"
                            autoFocus
                        />
                        <div className="mt-3 max-h-96 overflow-y-auto">
                            {loading && <p className="py-4 text-center text-xs text-gray-500">Searching...</p>}
                            {!loading && results.length === 0 && (
                                <p className="py-4 text-center text-xs text-gray-500">No questions found.</p>
                            )}
                            <ul className="divide-y divide-gray-200">
                                {results.map((q) => (
                                    <li key={q.id} className="flex items-start justify-between gap-3 py-3">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${TYPE_BADGE[q.type] ?? 'bg-gray-100'}`}>
                                                    {q.type_label}
                                                </span>
                                                <span className="text-xs text-gray-500">{q.difficulty} · {q.points} pts · v{q.version}</span>
                                            </div>
                                            <p className="mt-1 text-sm text-gray-700">{q.stem}</p>
                                        </div>
                                        <button
                                            onClick={() => attach(q.id)}
                                            className="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-500"
                                        >
                                            Add
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                )}

                {tab === 'create' && (
                    <div className="mt-4 grid grid-cols-2 gap-3">
                        {[
                            { type: 'single_select', label: 'Single Select' },
                            { type: 'multi_select', label: 'Multi Select' },
                            { type: 'coding', label: 'Coding' },
                            { type: 'rlhf', label: 'RLHF' },
                        ].map((t) => (
                            <Link
                                key={t.type}
                                href={route('admin.questions.create', t.type)}
                                className="rounded-lg border border-gray-200 p-4 text-center text-sm font-medium text-gray-700 hover:border-indigo-500 hover:bg-indigo-50"
                            >
                                {t.label}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
