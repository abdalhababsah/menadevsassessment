import AdminLayout from '@/Layouts/AdminLayout';
import InputError from '@/components/inputerror';
import InputLabel from '@/components/inputlabel';
import PrimaryButton from '@/components/primarybutton';
import TextInput from '@/components/textinput';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.quizzes.store'));
    };

    return (
        <AdminLayout>
            <Head title="Create Quiz" />

            <div className="mb-6">
                <Link href={route('admin.quizzes.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Quizzes
                </Link>
                <h1 className="mt-2 text-2xl font-bold text-gray-900">Create Quiz</h1>
                <p className="mt-1 text-sm text-gray-600">
                    Give your quiz a name. You can configure all settings after creation.
                </p>
            </div>

            <form onSubmit={submit} className="max-w-2xl">
                <div className="space-y-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div>
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="mt-1 block w-full"
                            required
                            isFocused
                        />
                        <InputError message={errors.title} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="description" value="Description (optional)" />
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={4}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm"
                        />
                        <InputError message={errors.description} className="mt-2" />
                    </div>
                </div>

                <div className="mt-6 flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Create Quiz</PrimaryButton>
                    <Link href={route('admin.quizzes.index')} className="text-sm text-gray-600 hover:text-gray-900">
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
