import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

interface UserRow {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    is_super_admin: boolean;
    last_login_at: string | null;
    roles: string[];
}

export default function Index({ users }: { users: UserRow[] }) {
    const handleDeactivate = (user: UserRow) => {
        router.delete(route('admin.users.destroy', user.id));
    };

    const handleReactivate = (user: UserRow) => {
        router.post(route('admin.users.reactivate', user.id));
    };

    return (
        <AdminLayout>
            <Head title="Users" />

            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-900">Users</h2>
                <Link
                    href={route('admin.users.create')}
                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                >
                    Invite User
                </Link>
            </div>

            <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Roles</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Login</th>
                            <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {users.map((user) => (
                            <tr key={user.id}>
                                <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {user.name}
                                    {user.is_super_admin && (
                                        <span className="ml-2 inline-flex rounded-full bg-purple-100 px-2 text-xs font-semibold text-purple-800">
                                            Super Admin
                                        </span>
                                    )}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{user.email}</td>
                                <td className="px-6 py-4 text-sm text-gray-500">
                                    {user.roles.length > 0 ? user.roles.join(', ') : <span className="text-gray-400">No roles</span>}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                    {user.is_active ? (
                                        <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold text-green-800">Active</span>
                                    ) : (
                                        <span className="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold text-gray-800">Inactive</span>
                                    )}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {user.last_login_at ?? <span className="text-gray-400">Never</span>}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <Link href={route('admin.users.edit', user.id)} className="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    {!user.is_super_admin && (
                                        user.is_active ? (
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <button className="ml-3 text-red-600 hover:text-red-900 cursor-pointer">
                                                        Deactivate
                                                    </button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Deactivate User?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Are you sure you want to deactivate {user.name}? This user will no longer be able to log in to the system.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction 
                                                            variant="destructive"
                                                            onClick={() => handleDeactivate(user)}
                                                        >
                                                            Deactivate User
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        ) : (
                                            <button onClick={() => handleReactivate(user)} className="ml-3 text-green-600 hover:text-green-900 cursor-pointer">
                                                Reactivate
                                            </button>
                                        )
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

        </AdminLayout>
    );
}
