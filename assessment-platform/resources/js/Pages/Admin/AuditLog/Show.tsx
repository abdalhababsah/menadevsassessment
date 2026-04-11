import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    log: {
        id: number;
        action: string;
        auditable_type: string;
        auditable_type_short: string;
        auditable_id: number;
        ip_address: string | null;
        created_at: string | null;
        changes: Record<string, unknown> | null;
        actor: {
            id: number;
            name: string;
            email: string;
        } | null;
    };
};

/**
 * Splits the `changes` payload into "before" and "after" columns.
 * - Keys like `previous_*` and `new_*` are paired up.
 * - Anything else lands in the "after" column.
 */
function splitDiff(changes: Record<string, unknown> | null): {
    before: Array<{ key: string; value: unknown }>;
    after: Array<{ key: string; value: unknown }>;
} {
    if (!changes) {
        return { before: [], after: [] };
    }

    const before: Array<{ key: string; value: unknown }> = [];
    const after: Array<{ key: string; value: unknown }> = [];

    for (const [key, value] of Object.entries(changes)) {
        if (key.startsWith('previous_') || key.startsWith('old_')) {
            before.push({ key: key.replace(/^(previous_|old_)/, ''), value });
        } else if (key.startsWith('new_')) {
            after.push({ key: key.replace(/^new_/, ''), value });
        } else {
            after.push({ key, value });
        }
    }

    return { before, after };
}

function renderValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }
    return String(value);
}

export default function AuditLogShow({ log }: Props) {
    const { before, after } = splitDiff(log.changes);

    return (
        <AdminLayout>
            <Head title={`Audit entry #${log.id}`} />

            <div className="mx-auto max-w-4xl">
                <Link
                    href="/admin/audit-log"
                    className="text-[11px] font-semibold uppercase tracking-wide text-gray-500 hover:text-gray-700"
                >
                    ← Audit log
                </Link>

                <header className="mt-3 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.28em] text-gray-500">
                        Entry #{log.id}
                    </p>
                    <h1 className="mt-1 font-mono text-xl font-semibold text-gray-950">{log.action}</h1>

                    <dl className="mt-4 grid grid-cols-2 gap-3 text-xs md:grid-cols-4">
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">Actor</dt>
                            <dd className="mt-0.5 font-semibold text-gray-900">
                                {log.actor ? log.actor.name : 'system'}
                            </dd>
                            {log.actor && (
                                <dd className="text-gray-500">{log.actor.email}</dd>
                            )}
                        </div>
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">Target</dt>
                            <dd className="mt-0.5 font-semibold text-gray-900">
                                {log.auditable_type_short} #{log.auditable_id}
                            </dd>
                            <dd className="font-mono text-[10px] text-gray-500">
                                {log.auditable_type}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">IP</dt>
                            <dd className="mt-0.5 font-mono text-xs text-gray-900">
                                {log.ip_address ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-[10px] uppercase tracking-wide text-gray-500">When</dt>
                            <dd className="mt-0.5 text-xs text-gray-900">
                                {log.created_at ? new Date(log.created_at).toLocaleString() : '—'}
                            </dd>
                        </div>
                    </dl>
                </header>

                <section className="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 className="text-sm font-semibold text-gray-950">Changes</h2>
                    <p className="mt-1 text-xs text-gray-500">
                        Entries with <code>previous_</code>/<code>old_</code> and <code>new_</code> keys
                        are paired as a before/after diff.
                    </p>

                    {log.changes === null ? (
                        <p className="mt-4 text-sm text-gray-500">No structured changes recorded.</p>
                    ) : (
                        <div className="mt-4 grid gap-3 md:grid-cols-2">
                            <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-gray-500">
                                    Before
                                </p>
                                {before.length === 0 ? (
                                    <p className="mt-2 text-xs text-gray-500">—</p>
                                ) : (
                                    <ul className="mt-2 space-y-2 text-xs">
                                        {before.map((entry) => (
                                            <li key={`before-${entry.key}`}>
                                                <div className="font-mono text-[11px] font-semibold text-gray-700">
                                                    {entry.key}
                                                </div>
                                                <pre className="mt-0.5 whitespace-pre-wrap break-words text-[11px] text-gray-900">
                                                    {renderValue(entry.value)}
                                                </pre>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                            <div className="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
                                <p className="text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                    After
                                </p>
                                {after.length === 0 ? (
                                    <p className="mt-2 text-xs text-gray-500">—</p>
                                ) : (
                                    <ul className="mt-2 space-y-2 text-xs">
                                        {after.map((entry) => (
                                            <li key={`after-${entry.key}`}>
                                                <div className="font-mono text-[11px] font-semibold text-gray-700">
                                                    {entry.key}
                                                </div>
                                                <pre className="mt-0.5 whitespace-pre-wrap break-words text-[11px] text-gray-900">
                                                    {renderValue(entry.value)}
                                                </pre>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                    )}
                </section>

                <section className="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 className="text-sm font-semibold text-gray-950">Raw payload</h2>
                    <pre className="mt-3 overflow-auto rounded-xl border border-gray-200 bg-gray-950 p-4 text-[11px] text-gray-100">
                        {JSON.stringify(log.changes, null, 2) ?? 'null'}
                    </pre>
                </section>
            </div>
        </AdminLayout>
    );
}
