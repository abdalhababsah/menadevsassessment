import AdminLayout from '@/Layouts/AdminLayout';
import SelectQuestionForm from '@/components/questionbuilder/selectquestionform';
import { Head, Link } from '@inertiajs/react';

export default function CreateSingleSelect({ tags }: { tags: { id: number; name: string }[] }) {
    return (
        <AdminLayout>
            <Head title="Create Single-Select Question" />
            <div className="mb-6">
                <Link href={route('admin.questions.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Question Bank
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">Create Single-Select Question</h1>
            </div>
            <SelectQuestionForm
                mode="single"
                action="create"
                submitUrl={route('admin.questions.store.single-select')}
                tags={tags}
                title="Question Details"
            />
        </AdminLayout>
    );
}
