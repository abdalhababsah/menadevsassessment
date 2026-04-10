import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Role {
    id: number;
    name: string;
    users_count: number;
    permissions_count: number;
    created_at: string;
}

export default function Index({ roles }: { roles: Role[] }) {
    const [deleteTarget, setDeleteTarget] = useState<Role | null>(null);
    const [replacementRoleId, setReplacementRoleId] = useState<string>('');
    const [cloneTarget, setCloneTarget] = useState<Role | null>(null);
    const [cloneName, setCloneName] = useState('');

    const handleDelete = () => {
        if (!deleteTarget || !replacementRoleId) return;
        router.delete(route('admin.roles.destroy', deleteTarget.id), {
            data: { replacement_role_id: parseInt(replacementRoleId) },
            onSuccess: () => setDeleteTarget(null),
        });
    };

    const handleClone = () => {
        if (!cloneTarget || !cloneName) return;
        router.post(route('admin.roles.clone', cloneTarget.id), {
            name: cloneName,
        }, {
            onSuccess: () => { setCloneTarget(null); setCloneName(''); },
        });
    };

    return (
        <AdminLayout>
            <Head title="Roles" />

            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-900">Roles</h2>
                <Link
                    href={route('admin.roles.create')}
                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    Create Role
                </Link>
            </div>

            <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Users</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Permissions</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created</th>
                            <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {roles.map((role) => (
                            <tr key={role.id}>
                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{role.name}</td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{role.users_count}</td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{role.permissions_count}</td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{role.created_at}</td>
                                <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link href={route('admin.roles.edit', role.id)} className="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    <button onClick={() => { setCloneTarget(role); setCloneName(`${role.name} (Copy)`); }} className="ml-3 text-gray-600 hover:text-gray-900">Clone</button>
                                    {role.name !== 'Super Admin' && (
                                        <button onClick={() => setDeleteTarget(role)} className="ml-3 text-red-600 hover:text-red-900">Delete</button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Delete Modal */}
            {deleteTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                        <h3 className="text-lg font-semibold text-gray-900">Delete Role: {deleteTarget.name}</h3>
                        <p className="mt-2 text-sm text-gray-600">Select a replacement role for affected users:</p>
                        <select
                            value={replacementRoleId}
                            onChange={(e) => setReplacementRoleId(e.target.value)}
                            className="mt-3 block w-full rounded-lg border-gray-300 shadow-sm"
                        >
                            <option value="">Select role...</option>
                            {roles.filter(r => r.id !== deleteTarget.id).map(r => (
                                <option key={r.id} value={r.id}>{r.name}</option>
                            ))}
                        </select>
                        <div className="mt-4 flex justify-end gap-3">
                            <button onClick={() => setDeleteTarget(null)} className="rounded-lg px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</button>
                            <button onClick={handleDelete} disabled={!replacementRoleId} className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500 disabled:opacity-50">Delete</button>
                        </div>
                    </div>
                </div>
            )}

            {/* Clone Modal */}
            {cloneTarget && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                        <h3 className="text-lg font-semibold text-gray-900">Clone Role: {cloneTarget.name}</h3>
                        <input
                            type="text"
                            value={cloneName}
                            onChange={(e) => setCloneName(e.target.value)}
                            className="mt-3 block w-full rounded-lg border-gray-300 shadow-sm"
                            placeholder="New role name"
                        />
                        <div className="mt-4 flex justify-end gap-3">
                            <button onClick={() => setCloneTarget(null)} className="rounded-lg px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</button>
                            <button onClick={handleClone} disabled={!cloneName} className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50">Clone</button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
