import AdminLayout from '@/Layouts/AdminLayout';
import CodingQuestionForm from '@/Components/QuestionBuilder/CodingQuestionForm';
import { Head, Link } from '@inertiajs/react';

export default function CreateCoding({ tags }: { tags: { id: number; name: string }[] }) {
    return (
        <AdminLayout>
            <Head title="Create Coding Question" />
            <div className="mb-6">
                <Link href={route('admin.questions.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Question Bank
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">Create Coding Question</h1>
            </div>
            <CodingQuestionForm
                action="create"
                submitUrl={route('admin.questions.store.coding')}
                tags={tags}
                title="Question Details"
            />
        </AdminLayout>
    );
}
