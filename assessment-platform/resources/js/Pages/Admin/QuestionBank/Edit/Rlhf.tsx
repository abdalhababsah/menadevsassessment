import AdminLayout from '@/Layouts/AdminLayout';
import RlhfQuestionForm from '@/Components/RlhfBuilder/RlhfQuestionForm';
import { CriterionInput } from '@/Components/RlhfBuilder/CriterionEditor';
import { FormFieldInput } from '@/Components/RlhfBuilder/FormFieldBuilder';
import { Head, Link } from '@inertiajs/react';

interface QuestionData {
    id: number;
    stem: string;
    instructions: string | null;
    difficulty: string;
    points: number;
    time_limit_seconds: number | null;
    version: number;
    tags: number[];
    rlhf_config: {
        number_of_turns: number;
        candidate_input_mode: string;
        model_a: string;
        model_b: string;
        generation_params: Record<string, string | number | boolean> | null;
        enable_pre_prompt_form: boolean;
        enable_post_prompt_form: boolean;
        enable_rewrite_step: boolean;
        enable_post_rewrite_form: boolean;
        guidelines_markdown: string | null;
    } | null;
    rlhf_criteria: CriterionInput[];
    rlhf_form_fields: FormFieldInput[];
}

export default function EditRlhf({
    question,
    tags,
}: {
    question: QuestionData;
    tags: { id: number; name: string }[];
}) {
    return (
        <AdminLayout>
            <Head title="Edit RLHF Question" />
            <div className="mb-6">
                <Link href={route('admin.questions.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Question Bank
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">
                    Edit RLHF Question <span className="text-sm font-normal text-gray-500">v{question.version}</span>
                </h1>
            </div>
            <RlhfQuestionForm
                action="update"
                method="put"
                submitUrl={route('admin.questions.update.rlhf', question.id)}
                tags={tags}
                initial={{
                    stem: question.stem,
                    instructions: question.instructions,
                    difficulty: question.difficulty,
                    points: question.points,
                    time_limit_seconds: question.time_limit_seconds,
                    tags: question.tags,
                    rlhf_config: question.rlhf_config,
                    rlhf_criteria: question.rlhf_criteria,
                    rlhf_form_fields: question.rlhf_form_fields,
                }}
                title="Question Details"
            />
        </AdminLayout>
    );
}
