import AdminLayout from '@/Layouts/AdminLayout';
import RlhfQuestionForm from '@/Components/RlhfBuilder/RlhfQuestionForm';
import { Head, Link } from '@inertiajs/react';

export default function CreateRlhf({ tags }: { tags: { id: number; name: string }[] }) {
    return (
        <AdminLayout>
            <Head title="Create RLHF Question" />
            <div className="mb-6">
                <Link href={route('admin.questions.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Question Bank
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">Create RLHF Question</h1>
            </div>
            <RlhfQuestionForm
                action="create"
                submitUrl={route('admin.questions.store.rlhf')}
                tags={tags}
                title="Question Details"
            />
        </AdminLayout>
    );
}
