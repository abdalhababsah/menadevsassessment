import AdminLayout from '@/Layouts/AdminLayout';
import InputError from '@/components/inputerror';
import InputLabel from '@/components/inputlabel';
import PrimaryButton from '@/components/primarybutton';
import TextInput from '@/components/textinput';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
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

interface UserData {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    is_super_admin: boolean;
    roles: string[];
}

export default function Edit({ user, roles }: { user: UserData; roles: string[] }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        roles: user.roles,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('admin.users.update', user.id));
    };

    const toggleRole = (role: string) => {
        setData('roles', data.roles.includes(role)
            ? data.roles.filter(r => r !== role)
            : [...data.roles, role]
        );
    };

    const handleDeactivate = () => {
        router.delete(route('admin.users.destroy', user.id));
    };

    return (
        <AdminLayout>
            <Head title={`Edit User: ${user.name}`} />

            <div className="mb-6">
                <Link href={route('admin.users.index')} className="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Users</Link>
                <h2 className="mt-2 text-2xl font-bold text-gray-900">Edit User</h2>
                <p className="text-sm text-gray-600">{user.email}</p>
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
                    </div>
                </div>

                <div className="mt-6 flex items-center gap-4">
                    <PrimaryButton disabled={processing}>Save Changes</PrimaryButton>
                    <Link href={route('admin.users.index')} className="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>

                    {!user.is_super_admin && user.is_active && (
                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <button
                                    type="button"
                                    className="ml-auto text-sm font-medium text-red-600 hover:text-red-700 cursor-pointer"
                                >
                                    Deactivate User
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
                                        onClick={handleDeactivate}
                                    >
                                        Deactivate User
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    )}
                </div>
            </form>
        </AdminLayout>
    );
}

