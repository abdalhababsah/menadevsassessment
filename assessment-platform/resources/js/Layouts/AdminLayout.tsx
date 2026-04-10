import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, useState } from 'react';
import { useAuth } from '@/Hooks/useAuth';
import { usePermissions } from '@/Hooks/usePermissions';

interface NavItem {
    label: string;
    href: string;
    permission?: string;
    permissions?: string[];
}

const navItems: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Quizzes', href: '/admin/quizzes', permission: 'quiz.view' },
    { label: 'Question Bank', href: '/admin/questions', permission: 'questionbank.view' },
    { label: 'Candidates', href: '/admin/candidates', permission: 'candidate.view' },
    { label: 'Results', href: '/admin/results', permission: 'results.view' },
    { label: 'Users', href: '/admin/users', permission: 'users.view' },
    { label: 'Roles', href: '/admin/roles', permission: 'roles.view' },
    { label: 'Audit Log', href: '/admin/audit-log', permission: 'system.auditLog' },
    { label: 'Settings', href: '/admin/settings', permission: 'system.settings' },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const { user } = useAuth();
    const { hasPermission, hasAnyPermission } = usePermissions();
    const { url } = usePage();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const isVisible = (item: NavItem): boolean => {
        if (!item.permission && !item.permissions) return true;
        if (item.permission) return hasPermission(item.permission);
        if (item.permissions) return hasAnyPermission(item.permissions);
        return false;
    };

    const isActive = (href: string): boolean => {
        return url.startsWith(href);
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Mobile sidebar overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/50 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-50 w-64 transform bg-gray-900 transition-transform duration-200 lg:translate-x-0 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                <div className="flex h-16 items-center px-6">
                    <h1 className="text-lg font-bold text-white">
                        Assessment Platform
                    </h1>
                </div>

                <nav className="mt-4 px-3">
                    {navItems.filter(isVisible).map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`mb-1 flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                isActive(item.href)
                                    ? 'bg-gray-800 text-white'
                                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                            }`}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>
            </aside>

            {/* Main content */}
            <div className="lg:pl-64">
                {/* Top bar */}
                <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:px-8">
                    <button
                        className="text-gray-500 lg:hidden"
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                    >
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div className="flex-1" />

                    <div className="flex items-center gap-4">
                        <span className="text-sm text-gray-600">
                            {user?.name}
                        </span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Log out
                        </Link>
                    </div>
                </header>

                {/* Page content */}
                <main className="p-4 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
