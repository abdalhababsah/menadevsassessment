import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type LogRow = {
    id: number;
    action: string;
    auditable_type: string;
    auditable_id: number;
    ip_address: string | null;
    created_at: string | null;
    actor: {
        id: number;
        name: string;
        email: string;
    } | null;
};

type Props = {
    logs: LogRow[];
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        actor_id: number | null;
        action: string | null;
        auditable_type: string | null;
        from: string | null;
        to: string | null;
    };
};

export default function AuditLogIndex({ logs, pagination, filters }: Props) {
    const [action, setAction] = useState(filters.action ?? '');
    const [auditableType, setAuditableType] = useState(filters.auditable_type ?? '');
    const [actorId, setActorId] = useState(filters.actor_id?.toString() ?? '');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    const submitFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            '/admin/audit-log',
            {
                action: action || undefined,
                auditable_type: auditableType || undefined,
                actor_id: actorId || undefined,
                from: from || undefined,
                to: to || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clearFilters = () => {
        router.get('/admin/audit-log', {}, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout>
            <Head title="Audit Log" />

            <div className="mx-auto max-w-7xl">
                <header className="mb-6">
                    <h1 className="text-2xl font-semibold text-gray-950">Audit log</h1>
                    <p className="mt-1 text-sm text-gray-600">
                        Every privileged action, chronologically.
                    </p>
                </header>

                <form
                    onSubmit={submitFilters}
                    className="mb-4 grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-5"
                >
                    <div>
                        <label className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                            Action starts with
                        </label>
                        <input
                            type="text"
                            value={action}
                            onChange={(e) => setAction(e.target.value)}
                            placeholder="e.g. quiz."
                            className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                            Target type
                        </label>
                        <input
                            type="text"
                            value={auditableType}
                            onChange={(e) => setAuditableType(e.target.value)}
                            placeholder="e.g. Quiz"
                            className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                            Actor ID
                        </label>
                        <input
                            type="number"
                            value={actorId}
                            onChange={(e) => setActorId(e.target.value)}
                            className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                            From
                        </label>
                        <input
                            type="date"
                            value={from}
                            onChange={(e) => setFrom(e.target.value)}
                            className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                            To
                        </label>
                        <input
                            type="date"
                            value={to}
                            onChange={(e) => setTo(e.target.value)}
                            className="mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-gray-950 focus:outline-none"
                        />
                    </div>
                    <div className="flex gap-2 md:col-span-5">
                        <button
                            type="submit"
                            className="rounded-lg bg-gray-950 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gray-800"
                        >
                            Apply filters
                        </button>
                        <button
                            type="button"
                            onClick={clearFilters}
                            className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Clear
                        </button>
                    </div>
                </form>

                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Timestamp', 'Actor', 'Action', 'Target', 'IP', ''].map((header) => (
                                    <th
                                        key={header}
                                        className="px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                                    >
                                        {header}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {logs.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-sm text-gray-500">
                                        No audit log entries match these filters.
                                    </td>
                                </tr>
                            ) : (
                                logs.map((log) => (
                                    <tr key={log.id} className="hover:bg-gray-50">
                                        <td className="px-3 py-3 text-xs text-gray-600">
                                            {log.created_at
                                                ? new Date(log.created_at).toLocaleString()
                                                : '—'}
                                        </td>
                                        <td className="px-3 py-3 text-sm">
                                            {log.actor ? (
                                                <div>
                                                    <div className="font-medium text-gray-900">
                                                        {log.actor.name}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {log.actor.email}
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-gray-500">system</span>
                                            )}
                                        </td>
                                        <td className="px-3 py-3 font-mono text-xs text-gray-900">
                                            {log.action}
                                        </td>
                                        <td className="px-3 py-3 text-xs text-gray-700">
                                            {log.auditable_type} #{log.auditable_id}
                                        </td>
                                        <td className="px-3 py-3 text-xs text-gray-500">
                                            {log.ip_address ?? '—'}
                                        </td>
                                        <td className="px-3 py-3 text-right">
                                            <Link
                                                href={`/admin/audit-log/${log.id}`}
                                                className="text-xs font-semibold text-gray-900 underline underline-offset-2 hover:text-gray-700"
                                            >
                                                View details
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {pagination.last_page > 1 && (
                    <div className="mt-4 flex items-center justify-between text-xs text-gray-600">
                        <span>
                            Page {pagination.current_page} of {pagination.last_page} ·{' '}
                            {pagination.total} entries
                        </span>
                        <div className="flex gap-2">
                            {pagination.current_page > 1 && (
                                <Link
                                    href={`/admin/audit-log?page=${pagination.current_page - 1}`}
                                    preserveState
                                    className="rounded-lg border border-gray-200 bg-white px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                            )}
                            {pagination.current_page < pagination.last_page && (
                                <Link
                                    href={`/admin/audit-log?page=${pagination.current_page + 1}`}
                                    preserveState
                                    className="rounded-lg border border-gray-200 bg-white px-3 py-1.5 font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
