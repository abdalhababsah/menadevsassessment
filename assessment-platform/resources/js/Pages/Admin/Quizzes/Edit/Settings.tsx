import AdminLayout from '@/Layouts/AdminLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface QuizSettings {
    id: number;
    title: string;
    description: string | null;
    time_limit_seconds: number | null;
    passing_score: number | null;
    randomize_questions: boolean;
    randomize_options: boolean;
    navigation_mode: string;
    camera_enabled: boolean;
    anti_cheat_enabled: boolean;
    max_fullscreen_exits: number;
    starts_at: string | null;
    ends_at: string | null;
    status: string;
}

export default function Settings({ quiz }: { quiz: QuizSettings }) {
    const { data, setData, put, processing, errors } = useForm({
        title: quiz.title,
        description: quiz.description ?? '',
        time_limit_seconds: quiz.time_limit_seconds,
        passing_score: quiz.passing_score,
        randomize_questions: quiz.randomize_questions,
        randomize_options: quiz.randomize_options,
        navigation_mode: quiz.navigation_mode,
        camera_enabled: quiz.camera_enabled,
        anti_cheat_enabled: quiz.anti_cheat_enabled,
        max_fullscreen_exits: quiz.max_fullscreen_exits,
        starts_at: quiz.starts_at ?? '',
        ends_at: quiz.ends_at ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.quizzes.update', quiz.id));
    };

    return (
        <AdminLayout>
            <Head title={`Edit: ${quiz.title}`} />

            <div className="mb-6">
                <Link href={route('admin.quizzes.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Quizzes
                </Link>
                <div className="mt-2 flex items-center gap-3">
                    <h1 className="text-2xl font-bold text-gray-900">{quiz.title}</h1>
                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${
                        quiz.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    }`}>
                        {quiz.status}
                    </span>
                </div>

                <div className="mt-4 flex gap-1 border-b border-gray-200 text-sm">
                    <Link
                        href={route('admin.quizzes.edit', quiz.id)}
                        className="border-b-2 border-indigo-600 px-3 py-2 font-medium text-indigo-600"
                    >
                        Settings
                    </Link>
                    <Link
                        href={route('admin.quizzes.builder', quiz.id)}
                        className="border-b-2 border-transparent px-3 py-2 text-gray-500 hover:text-gray-700"
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

            <form onSubmit={submit} className="max-w-3xl">
                <div className="space-y-6">
                    {/* Basics */}
                    <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h2 className="text-base font-semibold text-gray-900">Basics</h2>
                        <div className="mt-4 space-y-4">
                            <div>
                                <InputLabel htmlFor="title" value="Title" />
                                <TextInput
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="mt-1 block w-full"
                                    required
                                />
                                <InputError message={errors.title} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="description" value="Description" />
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={3}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                                />
                                <InputError message={errors.description} className="mt-2" />
                            </div>
                        </div>
                    </section>

                    {/* Timing & Scoring */}
                    <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h2 className="text-base font-semibold text-gray-900">Timing & Scoring</h2>
                        <div className="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="time_limit_seconds" value="Total Time Limit (seconds)" />
                                <TextInput
                                    id="time_limit_seconds"
                                    type="number"
                                    value={data.time_limit_seconds ?? ''}
                                    onChange={(e) => setData('time_limit_seconds', e.target.value ? parseInt(e.target.value, 10) : null)}
                                    className="mt-1 block w-full"
                                    placeholder="No limit"
                                />
                                <InputError message={errors.time_limit_seconds} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="passing_score" value="Passing Score (%)" />
                                <TextInput
                                    id="passing_score"
                                    type="number"
                                    step="0.01"
                                    value={data.passing_score ?? ''}
                                    onChange={(e) => setData('passing_score', e.target.value ? parseFloat(e.target.value) : null)}
                                    className="mt-1 block w-full"
                                    placeholder="No threshold"
                                />
                                <InputError message={errors.passing_score} className="mt-2" />
                            </div>
                        </div>
                    </section>

                    {/* Randomization & Navigation */}
                    <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h2 className="text-base font-semibold text-gray-900">Randomization & Navigation</h2>
                        <div className="mt-4 space-y-3">
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    checked={data.randomize_questions}
                                    onChange={(e) => setData('randomize_questions', e.target.checked)}
                                    className="rounded border-gray-300 text-indigo-600"
                                />
                                Randomize question order
                            </label>
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    checked={data.randomize_options}
                                    onChange={(e) => setData('randomize_options', e.target.checked)}
                                    className="rounded border-gray-300 text-indigo-600"
                                />
                                Randomize option order (single/multi-select)
                            </label>
                            <div>
                                <InputLabel htmlFor="navigation_mode" value="Navigation Mode" />
                                <select
                                    id="navigation_mode"
                                    value={data.navigation_mode}
                                    onChange={(e) => setData('navigation_mode', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                                >
                                    <option value="free">Free (back/forward navigation)</option>
                                    <option value="forward_only">Forward only (no going back)</option>
                                </select>
                                <InputError message={errors.navigation_mode} className="mt-2" />
                            </div>
                        </div>
                    </section>

                    {/* Anti-cheat */}
                    <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h2 className="text-base font-semibold text-gray-900">Proctoring & Anti-cheat</h2>
                        <div className="mt-4 space-y-3">
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    checked={data.camera_enabled}
                                    onChange={(e) => setData('camera_enabled', e.target.checked)}
                                    className="rounded border-gray-300 text-indigo-600"
                                />
                                Require camera (webcam snapshots)
                            </label>
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    type="checkbox"
                                    checked={data.anti_cheat_enabled}
                                    onChange={(e) => setData('anti_cheat_enabled', e.target.checked)}
                                    className="rounded border-gray-300 text-indigo-600"
                                />
                                Enable anti-cheat (track tab switches, paste, fullscreen exits)
                            </label>
                            <div>
                                <InputLabel htmlFor="max_fullscreen_exits" value="Max Fullscreen Exits Allowed" />
                                <TextInput
                                    id="max_fullscreen_exits"
                                    type="number"
                                    value={data.max_fullscreen_exits}
                                    onChange={(e) => setData('max_fullscreen_exits', parseInt(e.target.value, 10) || 0)}
                                    className="mt-1 block w-full"
                                    min={0}
                                />
                                <InputError message={errors.max_fullscreen_exits} className="mt-2" />
                            </div>
                        </div>
                    </section>

                    {/* Schedule */}
                    <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                        <h2 className="text-base font-semibold text-gray-900">Schedule</h2>
                        <p className="mt-1 text-xs text-gray-500">Optional. Leave empty to allow the quiz at any time.</p>
                        <div className="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="starts_at" value="Starts at" />
                                <input
                                    id="starts_at"
                                    type="datetime-local"
                                    value={data.starts_at}
                                    onChange={(e) => setData('starts_at', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                                />
                                <InputError message={errors.starts_at} className="mt-2" />
                            </div>
                            <div>
                                <InputLabel htmlFor="ends_at" value="Ends at" />
                                <input
                                    id="ends_at"
                                    type="datetime-local"
                                    value={data.ends_at}
                                    onChange={(e) => setData('ends_at', e.target.value)}
                                    className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                                />
                                <InputError message={errors.ends_at} className="mt-2" />
                            </div>
                        </div>
                    </section>
                </div>

                <div className="mt-6 flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save Settings</PrimaryButton>
                    <Link href={route('admin.quizzes.index')} className="text-sm text-gray-600 hover:text-gray-900">
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
