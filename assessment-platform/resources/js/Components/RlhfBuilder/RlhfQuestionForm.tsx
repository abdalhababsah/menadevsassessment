import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import StemEditor from '@/Components/QuestionBuilder/StemEditor';
import TextInput from '@/Components/TextInput';
import CriterionEditor, { CriterionInput } from '@/Components/RlhfBuilder/CriterionEditor';
import FormFieldBuilder, { FormFieldInput, FieldStage } from '@/Components/RlhfBuilder/FormFieldBuilder';
import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

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
    rlhf_config: {
        number_of_turns: number;
        candidate_input_mode: string;
        model_a: string;
        model_b: string;
        generation_params: Record<string, GenerationParamValue> | null;
        enable_pre_prompt_form: boolean;
        enable_post_prompt_form: boolean;
        enable_rewrite_step: boolean;
        enable_post_rewrite_form: boolean;
        guidelines_markdown: string | null;
    } | null;
    rlhf_criteria: CriterionInput[];
    rlhf_form_fields: FormFieldInput[];
}

interface Props {
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

type GenerationParamValue = string | number | boolean;

interface RlhfFormState {
    stem: string;
    instructions: string;
    difficulty: string;
    points: number;
    time_limit_seconds: number | null;
    tags: number[];
    number_of_turns: number;
    candidate_input_mode: string;
    model_a: string;
    model_b: string;
    generation_params: Record<string, GenerationParamValue> | null;
    enable_pre_prompt_form: boolean;
    enable_post_prompt_form: boolean;
    enable_rewrite_step: boolean;
    enable_post_rewrite_form: boolean;
    guidelines_markdown: string;
    criteria: CriterionInput[];
    form_fields: FormFieldInput[];
    force_new_version: boolean;
}

type Tab = 'basics' | 'generation' | 'criteria' | 'forms' | 'guidelines';

const TABS: { value: Tab; label: string }[] = [
    { value: 'basics', label: 'Basics' },
    { value: 'generation', label: 'Generation' },
    { value: 'criteria', label: 'Criteria' },
    { value: 'forms', label: 'Forms' },
    { value: 'guidelines', label: 'Guidelines' },
];

const FORM_STAGES: { value: FieldStage; label: string }[] = [
    { value: 'pre_prompt', label: 'Pre-prompt' },
    { value: 'post_prompt', label: 'Post-prompt' },
    { value: 'post_rewrite', label: 'Post-rewrite' },
];

export default function RlhfQuestionForm({ action, submitUrl, method = 'post', tags, initial, title }: Props) {
    const [activeTab, setActiveTab] = useState<Tab>('basics');
    const [activeStage, setActiveStage] = useState<FieldStage>('pre_prompt');

    const { data, setData, post, put, processing, errors } = useForm<RlhfFormState>({
        stem: initial?.stem ?? '',
        instructions: initial?.instructions ?? '',
        difficulty: initial?.difficulty ?? 'medium',
        points: initial?.points ?? 1,
        time_limit_seconds: initial?.time_limit_seconds ?? null,
        tags: initial?.tags ?? [],

        number_of_turns: initial?.rlhf_config?.number_of_turns ?? 1,
        candidate_input_mode: initial?.rlhf_config?.candidate_input_mode ?? 'text',
        model_a: initial?.rlhf_config?.model_a ?? 'claude-sonnet-4-5-20250514',
        model_b: initial?.rlhf_config?.model_b ?? 'gpt-4o',
        generation_params: initial?.rlhf_config?.generation_params ?? { temperature: 0.7, max_tokens: 2048 },
        enable_pre_prompt_form: initial?.rlhf_config?.enable_pre_prompt_form ?? false,
        enable_post_prompt_form: initial?.rlhf_config?.enable_post_prompt_form ?? true,
        enable_rewrite_step: initial?.rlhf_config?.enable_rewrite_step ?? false,
        enable_post_rewrite_form: initial?.rlhf_config?.enable_post_rewrite_form ?? false,
        guidelines_markdown: initial?.rlhf_config?.guidelines_markdown ?? '',

        criteria: initial?.rlhf_criteria ?? [],
        form_fields: initial?.rlhf_form_fields ?? [],

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
        <form onSubmit={submit} className="max-w-5xl">
            <div className="rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                <div className="border-b border-gray-200 px-6 pt-6">
                    <h2 className="text-lg font-semibold text-gray-900">{title}</h2>
                    <div className="mt-4 flex gap-1">
                        {TABS.map((tab) => (
                            <button
                                key={tab.value}
                                type="button"
                                onClick={() => setActiveTab(tab.value)}
                                className={`rounded-t-lg border-b-2 px-4 py-2 text-sm font-medium transition ${
                                    activeTab === tab.value
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="p-6">
                    {activeTab === 'basics' && (
                        <div className="space-y-6">
                            <div>
                                <InputLabel htmlFor="stem" value="Question Stem" />
                                <div className="mt-1">
                                    <StemEditor value={data.stem} onChange={(v) => setData('stem', v)} error={errors.stem} />
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
                            </div>
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <InputLabel htmlFor="difficulty" value="Difficulty" />
                                    <select
                                        id="difficulty"
                                        value={data.difficulty}
                                        onChange={(e) => setData('difficulty', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                    >
                                        {DIFFICULTIES.map((d) => <option key={d.value} value={d.value}>{d.label}</option>)}
                                    </select>
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
                                </div>
                                <div>
                                    <InputLabel htmlFor="time_limit_seconds" value="Time Limit (s)" />
                                    <TextInput
                                        id="time_limit_seconds"
                                        type="number"
                                        value={data.time_limit_seconds ?? ''}
                                        onChange={(e) => setData('time_limit_seconds', e.target.value ? parseInt(e.target.value, 10) : null)}
                                        className="mt-1 block w-full"
                                    />
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
                                                data.tags.includes(tag.id) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                            }`}
                                        >
                                            {tag.name}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'generation' && (
                        <div className="space-y-6">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="number_of_turns" value="Number of Turns (1-10)" />
                                    <TextInput
                                        id="number_of_turns"
                                        type="number"
                                        value={data.number_of_turns}
                                        onChange={(e) => setData('number_of_turns', parseInt(e.target.value, 10) || 1)}
                                        min={1}
                                        max={10}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.number_of_turns} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="candidate_input_mode" value="Candidate Input Mode" />
                                    <select
                                        id="candidate_input_mode"
                                        value={data.candidate_input_mode}
                                        onChange={(e) => setData('candidate_input_mode', e.target.value)}
                                        className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                                    >
                                        <option value="text">Text only</option>
                                        <option value="voice">Voice only</option>
                                        <option value="both">Text or voice</option>
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="model_a" value="Model A" />
                                    <TextInput
                                        id="model_a"
                                        value={data.model_a}
                                        onChange={(e) => setData('model_a', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                </div>
                                <div>
                                    <InputLabel htmlFor="model_b" value="Model B" />
                                    <TextInput
                                        id="model_b"
                                        value={data.model_b}
                                        onChange={(e) => setData('model_b', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Generation Parameters (JSON)" />
                                <textarea
                                    value={JSON.stringify(data.generation_params ?? {}, null, 2)}
                                    onChange={(e) => {
                                        try {
                                            setData('generation_params', JSON.parse(e.target.value));
                                        } catch {
                                            // ignore parse errors while typing
                                        }
                                    }}
                                    rows={4}
                                    className="mt-1 block w-full rounded-lg border-gray-300 font-mono text-xs"
                                />
                            </div>

                            <div className="space-y-2 rounded-lg bg-gray-50 p-4">
                                <h4 className="text-sm font-semibold text-gray-900">Form Stages</h4>
                                {[
                                    { key: 'enable_pre_prompt_form' as const, label: 'Enable pre-prompt form' },
                                    { key: 'enable_post_prompt_form' as const, label: 'Enable post-prompt form' },
                                    { key: 'enable_rewrite_step' as const, label: 'Enable rewrite step' },
                                    { key: 'enable_post_rewrite_form' as const, label: 'Enable post-rewrite form' },
                                ].map((toggle) => (
                                    <label key={toggle.key} className="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            checked={data[toggle.key] as boolean}
                                            onChange={(e) => setData(toggle.key, e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600"
                                        />
                                        {toggle.label}
                                    </label>
                                ))}
                            </div>
                        </div>
                    )}

                    {activeTab === 'criteria' && (
                        <CriterionEditor
                            criteria={data.criteria}
                            onChange={(c) => setData('criteria', c)}
                            errors={errors as Record<string, string | undefined>}
                        />
                    )}

                    {activeTab === 'forms' && (
                        <div>
                            <div className="mb-4 flex gap-1 border-b border-gray-200">
                                {FORM_STAGES.map((stage) => (
                                    <button
                                        key={stage.value}
                                        type="button"
                                        onClick={() => setActiveStage(stage.value)}
                                        className={`px-3 py-2 text-xs font-medium ${
                                            activeStage === stage.value ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500'
                                        }`}
                                    >
                                        {stage.label}
                                    </button>
                                ))}
                            </div>
                            <FormFieldBuilder
                                stage={activeStage}
                                fields={data.form_fields}
                                onChange={(f) => setData('form_fields', f)}
                                errors={errors as Record<string, string | undefined>}
                            />
                        </div>
                    )}

                    {activeTab === 'guidelines' && (
                        <div>
                            <InputLabel value="Guidelines (markdown, shown to candidate)" />
                            <textarea
                                value={data.guidelines_markdown ?? ''}
                                onChange={(e) => setData('guidelines_markdown', e.target.value)}
                                rows={16}
                                className="mt-1 block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm"
                                placeholder="# Guidelines&#10;&#10;Explain how the candidate should evaluate responses..."
                            />
                        </div>
                    )}
                </div>

                {action === 'update' && (
                    <div className="border-t border-gray-200 bg-amber-50 px-6 py-3">
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
