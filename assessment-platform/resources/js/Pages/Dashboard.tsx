import AdminLayout from '@/Layouts/AdminLayout';
import { useAuth } from '@/Hooks/useAuth';
import { Head } from '@inertiajs/react';

export default function Dashboard() {
    const { user } = useAuth();

    return (
        <AdminLayout>
            <Head title="Dashboard" />

            <div className="mb-6">
                <h2 className="text-2xl font-bold text-gray-900">
                    Dashboard
                </h2>
                <p className="mt-1 text-sm text-gray-600">
                    Welcome back, {user?.name}
                </p>
            </div>

            <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <p className="text-gray-900">
                    You're logged in!
                </p>
            </div>
        </AdminLayout>
    );
}
