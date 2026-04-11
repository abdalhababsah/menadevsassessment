import AdminLayout from '@/Layouts/AdminLayout';
import InputError from '@/components/inputerror';
import InputLabel from '@/components/inputlabel';
import PrimaryButton from '@/components/primarybutton';
import TextInput from '@/components/textinput';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Create({ roles }: { roles: string[] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        roles: [] as string[],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.users.store'));
    };

    const toggleRole = (role: string) => {
        setData('roles', data.roles.includes(role)
            ? data.roles.filter(r => r !== role)
            : [...data.roles, role]
        );
    };

    return (
        <AdminLayout>
            <Head title="Invite User" />

            <div className="mb-6">
                <Link href={route('admin.users.index')} className="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Users</Link>
                <h2 className="mt-2 text-2xl font-bold text-gray-900">Invite User</h2>
            </div>

            <form onSubmit={submit} className="max-w-2xl">
                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div>
                        <InputLabel htmlFor="name" value="Name" />
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full"
                            required
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1 block w-full"
                            required
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <div className="mt-6">
                        <InputLabel value="Roles" />
                        <div className="mt-2 space-y-2">
                            {roles.map((role) => (
                                <label key={role} className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={data.roles.includes(role)}
                                        onChange={() => toggleRole(role)}
                                        className="rounded border-gray-300 text-indigo-600"
                                    />
                                    <span className="text-sm text-gray-700">{role}</span>
                                </label>
                            ))}
                        </div>
                        <InputError message={errors.roles} className="mt-2" />
                    </div>
                </div>

                <div className="mt-6 flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Send Invitation</PrimaryButton>
                    <Link href={route('admin.users.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                </div>
            </form>
        </AdminLayout>
    );
}
