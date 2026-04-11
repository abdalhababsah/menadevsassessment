import AdminLayout from '@/Layouts/AdminLayout';
import SelectQuestionForm from '@/components/questionbuilder/selectquestionform';
import { type QuestionOption as QuestionOptionInput } from '@/components/questionbuilder/optionsbuilder';
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
    options: QuestionOptionInput[];
}

export default function EditSingleSelect({
    question,
    tags,
}: {
    question: QuestionData;
    tags: { id: number; name: string }[];
}) {
    return (
        <AdminLayout>
            <Head title="Edit Single-Select Question" />
            <div className="mb-6">
                <Link href={route('admin.questions.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Question Bank
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">
                    Edit Question <span className="text-sm font-normal text-gray-500">v{question.version}</span>
                </h1>
            </div>
            <SelectQuestionForm
                mode="single"
                action="update"
                method="put"
                submitUrl={route('admin.questions.update.single-select', question.id)}
                tags={tags}
                initial={{
                    stem: question.stem,
                    instructions: question.instructions,
                    difficulty: question.difficulty,
                    points: question.points,
                    time_limit_seconds: question.time_limit_seconds,
                    tags: question.tags,
                    options: question.options,
                }}
                title="Question Details"
            />
        </AdminLayout>
    );
}
