import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import OptionsBuilder, { QuestionOptionInput } from '@/Components/QuestionBuilder/OptionsBuilder';
import StemEditor from '@/Components/QuestionBuilder/StemEditor';
import TextInput from '@/Components/TextInput';
import { useForm, Link } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Tag {
    id: number;
    name: string;
}

interface InitialData {
    stem: string;
    instructions: string | null;
    difficulty: string;
    points: number;
    time_limit_seconds: number | null;
    tags: number[];
    options: QuestionOptionInput[];
}

interface Props {
    mode: 'single' | 'multi';
    action: 'create' | 'update';
    submitUrl: string;
    method?: 'post' | 'put';
    tags: Tag[];
    initial?: InitialData;
    title: string;
}

const DIFFICULTIES = [
    { value: 'easy', label: 'Easy' },
    { value: 'medium', label: 'Medium' },
    { value: 'hard', label: 'Hard' },
];

export default function SelectQuestionForm({
    mode,
    action,
    submitUrl,
    method = 'post',
    tags,
    initial,
    title,
}: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        stem: initial?.stem ?? '',
        instructions: initial?.instructions ?? '',
        difficulty: initial?.difficulty ?? 'medium',
        points: initial?.points ?? 1,
        time_limit_seconds: initial?.time_limit_seconds ?? null as number | null,
        tags: initial?.tags ?? [],
        options: initial?.options ?? [
            { content: '', content_type: 'text' as const, is_correct: mode === 'single', position: 0 },
            { content: '', content_type: 'text' as const, is_correct: false, position: 1 },
        ],
        force_new_version: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (method === 'put') {
            put(submitUrl);
        } else {
            post(submitUrl);
        }
    };

    const toggleTag = (tagId: number) => {
        setData('tags', data.tags.includes(tagId)
            ? data.tags.filter((id) => id !== tagId)
            : [...data.tags, tagId]
        );
    };

    return (
        <form onSubmit={submit} className="max-w-4xl">
            <div className="space-y-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 className="text-lg font-semibold text-gray-900">{title}</h2>

                <div>
                    <InputLabel htmlFor="stem" value="Question Stem" />
                    <div className="mt-1">
                        <StemEditor
                            value={data.stem}
                            onChange={(v) => setData('stem', v)}
                            error={errors.stem}
                        />
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="instructions" value="Instructions (optional)" />
                    <textarea
                        id="instructions"
                        value={data.instructions ?? ''}
                        onChange={(e) => setData('instructions', e.target.value)}
                        rows={2}
                        className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                    />
                    <InputError message={errors.instructions} className="mt-1" />
                </div>

                <div className="grid grid-cols-3 gap-4">
                    <div>
                        <InputLabel htmlFor="difficulty" value="Difficulty" />
                        <select
                            id="difficulty"
                            value={data.difficulty}
                            onChange={(e) => setData('difficulty', e.target.value)}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                        >
                            {DIFFICULTIES.map((d) => (
                                <option key={d.value} value={d.value}>{d.label}</option>
                            ))}
                        </select>
                        <InputError message={errors.difficulty} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="points" value="Points" />
                        <TextInput
                            id="points"
                            type="number"
                            value={data.points}
                            onChange={(e) => setData('points', parseFloat(e.target.value) || 0)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.points} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="time_limit_seconds" value="Time Limit (s, optional)" />
                        <TextInput
                            id="time_limit_seconds"
                            type="number"
                            value={data.time_limit_seconds ?? ''}
                            onChange={(e) => setData('time_limit_seconds', e.target.value ? parseInt(e.target.value, 10) : null)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.time_limit_seconds} className="mt-1" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Tags" />
                    <div className="mt-2 flex flex-wrap gap-2">
                        {tags.length === 0 && <p className="text-xs text-gray-500">No tags available</p>}
                        {tags.map((tag) => (
                            <button
                                key={tag.id}
                                type="button"
                                onClick={() => toggleTag(tag.id)}
                                className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                                    data.tags.includes(tag.id)
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                            >
                                {tag.name}
                            </button>
                        ))}
                    </div>
                </div>

                <div>
                    <InputLabel value={`Options (${mode === 'single' ? 'select 1 correct' : 'select 1+ correct'})`} />
                    <div className="mt-2">
                        <OptionsBuilder
                            options={data.options}
                            onChange={(options) => setData('options', options)}
                            mode={mode}
                            error={errors.options}
                        />
                    </div>
                </div>

                {action === 'update' && (
                    <div className="rounded-lg bg-amber-50 p-3">
                        <label className="flex items-center gap-2 text-sm text-amber-900">
                            <input
                                type="checkbox"
                                checked={data.force_new_version}
                                onChange={(e) => setData('force_new_version', e.target.checked)}
                                className="rounded border-amber-300 text-amber-600"
                            />
                            Save as a new version (preserves the original for existing quizzes)
                        </label>
                    </div>
                )}
            </div>

            <div className="mt-6 flex items-center gap-4">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                >
                    {action === 'create' ? 'Create Question' : 'Save Changes'}
                </button>
                <Link href={route('admin.questions.index')} className="text-sm text-gray-600 hover:text-gray-900">
                    Cancel
                </Link>
            </div>
        </form>
    );
}
