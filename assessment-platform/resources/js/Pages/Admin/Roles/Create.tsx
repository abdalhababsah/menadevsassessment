import AdminLayout from '@/Layouts/AdminLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import PermissionPicker from '@/Components/PermissionPicker';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Create({ permissionGroups }: { permissionGroups: Record<string, string[]> }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        permissions: [] as string[],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.roles.store'));
    };

    return (
        <AdminLayout>
            <Head title="Create Role" />

            <div className="mb-6">
                <Link href={route('admin.roles.index')} className="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Roles</Link>
                <h2 className="mt-2 text-2xl font-bold text-gray-900">Create Role</h2>
            </div>

            <form onSubmit={submit} className="max-w-3xl">
                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                    <div>
                        <InputLabel htmlFor="name" value="Role Name" />
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full max-w-md"
                            required
                        />
                        <InputError message={errors.name} className="mt-2" />
                    </div>

                    <div className="mt-6">
                        <InputLabel value="Permissions" />
                        <InputError message={errors.permissions} className="mt-1" />
                        <div className="mt-2">
                            <PermissionPicker
                                permissionGroups={permissionGroups}
                                selected={data.permissions}
                                onChange={(permissions) => setData('permissions', permissions)}
                            />
                        </div>
                    </div>
                </div>

                <div className="mt-6 flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Create Role</PrimaryButton>
                    <Link href={route('admin.roles.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                </div>
            </form>
        </AdminLayout>
    );
}
