import AdminLayout from '@/Layouts/AdminLayout';
import SelectQuestionForm from '@/components/questionbuilder/selectquestionform';
import { Head, Link } from '@inertiajs/react';

export default function CreateMultiSelect({ tags }: { tags: { id: number; name: string }[] }) {
    return (
        <AdminLayout>
            <Head title="Create Multi-Select Question" />
            <div className="mb-6">
                <Link href={route('admin.questions.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Question Bank
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">Create Multi-Select Question</h1>
            </div>
            <SelectQuestionForm
                mode="multi"
                action="create"
                submitUrl={route('admin.questions.store.multi-select')}
                tags={tags}
                title="Question Details"
            />
        </AdminLayout>
    );
}
