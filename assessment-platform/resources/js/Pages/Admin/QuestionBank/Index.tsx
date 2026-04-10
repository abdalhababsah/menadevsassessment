import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState, FormEventHandler } from 'react';

interface QuestionRow {
    id: number;
    stem: string;
    type: string;
    type_label: string;
    difficulty: string;
    difficulty_label: string;
    points: string;
    tags: { id: number; name: string }[];
    creator: { id: number; name: string } | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedQuestions {
    data: QuestionRow[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Filters {
    type?: string;
    difficulty?: string;
    creator?: string;
    tags?: number[] | string;
    q?: string;
    created_after?: string;
    created_before?: string;
}

interface Props {
    questions: PaginatedQuestions;
    tags: { id: number; name: string }[];
    creators: { id: number; name: string }[];
    filters: Filters;
    sort: string;
}

const TYPES = [
    { value: 'single_select', label: 'Single Select' },
    { value: 'multi_select', label: 'Multi Select' },
    { value: 'coding', label: 'Coding' },
    { value: 'rlhf', label: 'RLHF' },
];

const DIFFICULTIES = [
    { value: 'easy', label: 'Easy' },
    { value: 'medium', label: 'Medium' },
    { value: 'hard', label: 'Hard' },
];

const TYPE_BADGE_STYLES: Record<string, string> = {
    single_select: 'bg-blue-100 text-blue-800',
    multi_select: 'bg-indigo-100 text-indigo-800',
    coding: 'bg-emerald-100 text-emerald-800',
    rlhf: 'bg-purple-100 text-purple-800',
};

const DIFFICULTY_BADGE_STYLES: Record<string, string> = {
    easy: 'bg-green-100 text-green-800',
    medium: 'bg-yellow-100 text-yellow-800',
    hard: 'bg-red-100 text-red-800',
};

export default function Index({ questions, tags, creators, filters, sort }: Props) {
    const [searchTerm, setSearchTerm] = useState((filters.q as string) ?? '');
    const [showCreateModal, setShowCreateModal] = useState(false);

    const selectedTagIds: number[] = Array.isArray(filters.tags)
        ? filters.tags
        : typeof filters.tags === 'string' && filters.tags.length > 0
            ? filters.tags.split(',').map(Number)
            : [];

    const buildQuery = (overrides: Partial<Filters & { sort: string }>) => {
        const next = {
            'filter[type]': filters.type ?? '',
            'filter[difficulty]': filters.difficulty ?? '',
            'filter[creator]': filters.creator ?? '',
            'filter[tags]': selectedTagIds.join(','),
            'filter[q]': filters.q ?? '',
            'filter[created_after]': filters.created_after ?? '',
            'filter[created_before]': filters.created_before ?? '',
            sort,
            ...Object.fromEntries(
                Object.entries(overrides).map(([k, v]) =>
                    k === 'sort' ? ['sort', v] : [`filter[${k}]`, v ?? '']
                )
            ),
        };

        return Object.fromEntries(Object.entries(next).filter(([, v]) => v !== '' && v !== undefined));
    };

    const applyFilter = (key: keyof Filters, value: string | number[] | undefined) => {
        const stringValue = Array.isArray(value) ? value.join(',') : (value as string | undefined);
        router.get(route('admin.questions.index'), buildQuery({ [key]: stringValue }), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilter('q', searchTerm);
    };

    const toggleType = (type: string) => {
        applyFilter('type', filters.type === type ? undefined : type);
    };

    const toggleDifficulty = (difficulty: string) => {
        applyFilter('difficulty', filters.difficulty === difficulty ? undefined : difficulty);
    };

    const toggleTag = (tagId: number) => {
        const next = selectedTagIds.includes(tagId)
            ? selectedTagIds.filter((id) => id !== tagId)
            : [...selectedTagIds, tagId];
        applyFilter('tags', next);
    };

    const clearFilters = () => {
        router.get(route('admin.questions.index'));
    };

    return (
        <AdminLayout>
            <Head title="Question Bank" />

            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">Question Bank</h2>
                    <p className="mt-1 text-sm text-gray-600">{questions.total} questions</p>
                </div>
                <button
                    onClick={() => setShowCreateModal(true)}
                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    Create Question
                </button>
            </div>

            <div className="grid grid-cols-12 gap-6">
                {/* Filters Sidebar */}
                <aside className="col-span-12 lg:col-span-3">
                    <div className="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-gray-900">Filters</h3>
                            <button onClick={clearFilters} className="text-xs text-gray-500 hover:text-gray-700">
                                Clear all
                            </button>
                        </div>

                        <div className="mt-4">
                            <h4 className="text-xs font-medium uppercase tracking-wide text-gray-500">Type</h4>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {TYPES.map((type) => (
                                    <button
                                        key={type.value}
                                        onClick={() => toggleType(type.value)}
                                        className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                                            filters.type === type.value
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        }`}
                                    >
                                        {type.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="mt-4">
                            <h4 className="text-xs font-medium uppercase tracking-wide text-gray-500">Difficulty</h4>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {DIFFICULTIES.map((difficulty) => (
                                    <button
                                        key={difficulty.value}
                                        onClick={() => toggleDifficulty(difficulty.value)}
                                        className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                                            filters.difficulty === difficulty.value
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        }`}
                                    >
                                        {difficulty.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="mt-4">
                            <h4 className="text-xs font-medium uppercase tracking-wide text-gray-500">Tags</h4>
                            <div className="mt-2 max-h-48 space-y-1 overflow-y-auto">
                                {tags.map((tag) => (
                                    <label key={tag.id} className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            checked={selectedTagIds.includes(tag.id)}
                                            onChange={() => toggleTag(tag.id)}
                                            className="rounded border-gray-300 text-indigo-600"
                                        />
                                        <span className="text-sm text-gray-700">{tag.name}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="mt-4">
                            <h4 className="text-xs font-medium uppercase tracking-wide text-gray-500">Creator</h4>
                            <select
                                value={filters.creator ?? ''}
                                onChange={(e) => applyFilter('creator', e.target.value || undefined)}
                                className="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                            >
                                <option value="">All creators</option>
                                {creators.map((creator) => (
                                    <option key={creator.id} value={creator.id}>{creator.name}</option>
                                ))}
                            </select>
                        </div>

                        <div className="mt-4">
                            <h4 className="text-xs font-medium uppercase tracking-wide text-gray-500">Created Date</h4>
                            <input
                                type="date"
                                value={filters.created_after ?? ''}
                                onChange={(e) => applyFilter('created_after', e.target.value || undefined)}
                                className="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                                placeholder="From"
                            />
                            <input
                                type="date"
                                value={filters.created_before ?? ''}
                                onChange={(e) => applyFilter('created_before', e.target.value || undefined)}
                                className="mt-2 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                                placeholder="To"
                            />
                        </div>
                    </div>
                </aside>

                {/* Main Table */}
                <div className="col-span-12 lg:col-span-9">
                    <form onSubmit={handleSearch} className="mb-4">
                        <input
                            type="search"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Search question stems..."
                            className="block w-full rounded-lg border-gray-300 shadow-sm"
                        />
                    </form>

                    <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Question</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Difficulty</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tags</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Creator</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {questions.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-12 text-center text-sm text-gray-500">
                                            No questions found.
                                        </td>
                                    </tr>
                                ) : (
                                    questions.data.map((question) => (
                                        <tr key={question.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-900">{question.stem}</td>
                                            <td className="px-6 py-4 text-sm">
                                                <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${TYPE_BADGE_STYLES[question.type] ?? 'bg-gray-100'}`}>
                                                    {question.type_label}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${DIFFICULTY_BADGE_STYLES[question.difficulty] ?? 'bg-gray-100'}`}>
                                                    {question.difficulty_label}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-500">
                                                {question.tags.map((t) => t.name).join(', ') || <span className="text-gray-400">—</span>}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{question.creator?.name ?? '—'}</td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{question.created_at}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {questions.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between">
                            <p className="text-sm text-gray-700">
                                Page {questions.current_page} of {questions.last_page}
                            </p>
                            <div className="flex gap-1">
                                {questions.links.map((link, i) => (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.visit(link.url, { preserveState: true, preserveScroll: true })}
                                        className={`rounded-lg px-3 py-1 text-sm ${
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : link.url
                                                    ? 'bg-white text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50'
                                                    : 'cursor-not-allowed text-gray-300'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Create Question Type Picker Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                        <h3 className="text-lg font-semibold text-gray-900">Create New Question</h3>
                        <p className="mt-1 text-sm text-gray-600">Choose a question type</p>
                        <div className="mt-4 grid grid-cols-2 gap-3">
                            {TYPES.map((type) => (
                                <Link
                                    key={type.value}
                                    href="#"
                                    className="rounded-lg border border-gray-200 p-4 text-center text-sm font-medium text-gray-700 hover:border-indigo-500 hover:bg-indigo-50"
                                >
                                    {type.label}
                                </Link>
                            ))}
                        </div>
                        <div className="mt-4 flex justify-end">
                            <button
                                onClick={() => setShowCreateModal(false)}
                                className="rounded-lg px-4 py-2 text-sm text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
