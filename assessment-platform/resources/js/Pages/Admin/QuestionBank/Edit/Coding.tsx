import AdminLayout from '@/Layouts/AdminLayout';
import CodingQuestionForm from '@/Components/QuestionBuilder/CodingQuestionForm';
import { CodingTestCaseInput } from '@/Components/QuestionBuilder/CodingConfig';
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
    coding_config: {
        allowed_languages: string[];
        starter_code: Record<string, string> | null;
        time_limit_ms: number;
        memory_limit_mb: number;
    } | null;
    test_cases: CodingTestCaseInput[];
}

export default function EditCoding({
    question,
    tags,
}: {
    question: QuestionData;
    tags: { id: number; name: string }[];
}) {
    return (
        <AdminLayout>
            <Head title="Edit Coding Question" />
            <div className="mb-6">
                <Link href={route('admin.questions.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Question Bank
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">
                    Edit Question <span className="text-sm font-normal text-gray-500">v{question.version}</span>
                </h1>
            </div>
            <CodingQuestionForm
                action="update"
                method="put"
                submitUrl={route('admin.questions.update.coding', question.id)}
                tags={tags}
                initial={{
                    stem: question.stem,
                    instructions: question.instructions,
                    difficulty: question.difficulty,
                    points: question.points,
                    time_limit_seconds: question.time_limit_seconds,
                    tags: question.tags,
                    coding_config: question.coding_config,
                    test_cases: question.test_cases,
                }}
                title="Question Details"
            />
        </AdminLayout>
    );
}
