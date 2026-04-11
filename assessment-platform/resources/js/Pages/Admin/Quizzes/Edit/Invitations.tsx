import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
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
import { toast } from "sonner";

interface InvitationRow {
    id: number;
    token: string;
    public_url: string;
    max_uses: number | null;
    uses_count: number;
    expires_at: string | null;
    email_domain_restriction: string | null;
    revoked_at: string | null;
    created_at: string;
    status: string;
    status_label: string;
    is_usable: boolean;
}

interface QuizSummary {
    id: number;
    title: string;
    status: string;
}

const STATUS_STYLES: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    expired: 'bg-amber-100 text-amber-800',
    exhausted: 'bg-orange-100 text-orange-800',
    revoked: 'bg-red-100 text-red-800',
};

export default function Invitations({
    quiz,
    invitations,
}: {
    quiz: QuizSummary;
    invitations: InvitationRow[];
}) {
    const [showCreate, setShowCreate] = useState(false);
    const [copiedId, setCopiedId] = useState<number | null>(null);

    const copyLink = async (invitation: InvitationRow) => {
        try {
            await navigator.clipboard.writeText(invitation.public_url);
            setCopiedId(invitation.id);
            setTimeout(() => setCopiedId(null), 1500);
            toast.success("Link copied to clipboard");
        } catch {
            toast.error("Failed to copy link. Please copy it manually.");
        }
    };

    const revoke = (invitation: InvitationRow) => {
        router.delete(route('admin.quizzes.invitations.destroy', [quiz.id, invitation.id]), {
            preserveScroll: true,
            onSuccess: () => toast.success("Invitation revoked"),
        });
    };

    const truncatedToken = (token: string) => `${token.slice(0, 8)}…${token.slice(-4)}`;

    return (
        <AdminLayout>
            <Head title={`Invitations: ${quiz.title}`} />

            <div className="mb-6">
                <Link href={route('admin.quizzes.index')} className="text-sm text-gray-500 hover:text-gray-700">
                    &larr; Back to Quizzes
                </Link>
                <div className="mt-2 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">{quiz.title}</h1>
                        <p className="text-sm text-gray-600">Invitations</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setShowCreate(true)}
                        className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                    >
                        Create Invitation
                    </button>
                </div>

                <div className="mt-4 flex gap-1 border-b border-gray-200 text-sm">
                    <Link
                        href={route('admin.quizzes.edit', quiz.id)}
                        className="border-b-2 border-transparent px-3 py-2 text-gray-500 hover:text-gray-700"
                    >
                        Settings
                    </Link>
                    <Link
                        href={route('admin.quizzes.builder', quiz.id)}
                        className="border-b-2 border-transparent px-3 py-2 text-gray-500 hover:text-gray-700"
                    >
                        Builder
                    </Link>
                    <Link
                        href={route('admin.quizzes.invitations.index', quiz.id)}
                        className="border-b-2 border-indigo-600 px-3 py-2 font-medium text-indigo-600"
                    >
                        Invitations
                    </Link>
                </div>
            </div>

            <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Token</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Uses</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Expires</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Domain</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {invitations.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-6 py-8 text-center text-sm text-gray-500">
                                    No invitations yet. Create one to share the quiz with candidates.
                                </td>
                            </tr>
                        )}
                        {invitations.map((invitation) => (
                            <tr key={invitation.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4">
                                    <button
                                        type="button"
                                        onClick={() => copyLink(invitation)}
                                        className="font-mono text-xs text-indigo-600 hover:text-indigo-800"
                                        title={invitation.public_url}
                                    >
                                        {truncatedToken(invitation.token)}
                                    </button>
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {invitation.uses_count}
                                    {invitation.max_uses !== null && ` / ${invitation.max_uses}`}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {invitation.expires_at ?? <span className="text-gray-400">Never</span>}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                    {invitation.email_domain_restriction ?? <span className="text-gray-400">Any</span>}
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-sm">
                                    <span className={`inline-flex rounded-full px-2 py-1 text-xs font-medium ${STATUS_STYLES[invitation.status] ?? 'bg-gray-100 text-gray-800'}`}>
                                        {invitation.status_label}
                                    </span>
                                </td>
                                <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <button
                                        type="button"
                                        onClick={() => copyLink(invitation)}
                                        className="text-indigo-600 hover:text-indigo-800"
                                    >
                                        {copiedId === invitation.id ? 'Copied!' : 'Copy link'}
                                    </button>
                                    {invitation.is_usable && (
                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <button
                                                    type="button"
                                                    className="ml-3 text-red-600 hover:text-red-900 cursor-pointer"
                                                >
                                                    Revoke
                                                </button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Revoke Invitation?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Are you sure you want to revoke this invitation? Candidates who have already started their assessment will still be able to complete it, but new access attempts will be blocked.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction 
                                                        variant="destructive"
                                                        onClick={() => revoke(invitation)}
                                                    >
                                                        Revoke Invitation
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {showCreate && (
                <CreateInvitationModal
                    quizId={quiz.id}
                    onClose={() => setShowCreate(false)}
                />
            )}
        </AdminLayout>
    );
}

function CreateInvitationModal({
    quizId,
    onClose,
}: {
    quizId: number;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors } = useForm({
        max_uses: null as number | null,
        expires_at: '',
        email_domain_restriction: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.quizzes.invitations.store', quizId), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <form onSubmit={submit} className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 className="text-lg font-semibold text-gray-900">New Invitation</h3>
                <p className="mt-1 text-xs text-gray-600">
                    Generate a shareable link to give candidates access to this quiz.
                </p>

                <div className="mt-4 space-y-4">
                    <div>
                        <label className="block text-xs font-medium text-gray-700">Max Uses (optional)</label>
                        <input
                            type="number"
                            value={data.max_uses ?? ''}
                            onChange={(e) => setData('max_uses', e.target.value ? parseInt(e.target.value, 10) : null)}
                            min={1}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            placeholder="Unlimited"
                        />
                        {errors.max_uses && <p className="mt-1 text-xs text-red-600">{errors.max_uses}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700">Expires At (optional)</label>
                        <input
                            type="datetime-local"
                            value={data.expires_at}
                            onChange={(e) => setData('expires_at', e.target.value)}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                        />
                        {errors.expires_at && <p className="mt-1 text-xs text-red-600">{errors.expires_at}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700">Email Domain Restriction (optional)</label>
                        <input
                            type="text"
                            value={data.email_domain_restriction}
                            onChange={(e) => setData('email_domain_restriction', e.target.value)}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-sm"
                            placeholder="example.com"
                        />
                        <p className="mt-1 text-xs text-gray-500">Only candidates with emails from this domain can use the link.</p>
                        {errors.email_domain_restriction && <p className="mt-1 text-xs text-red-600">{errors.email_domain_restriction}</p>}
                    </div>
                </div>

                <div className="mt-5 flex justify-end gap-2">
                    <button type="button" onClick={onClose} className="rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                    >
                        Create
                    </button>
                </div>
            </form>
        </div>
    );
}
