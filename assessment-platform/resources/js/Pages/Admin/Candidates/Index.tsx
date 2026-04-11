import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { usePermissions } from '@/Hooks/usePermissions';
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

interface CandidateRow {
    id: number;
    name: string | null;
    email: string;
    is_guest: boolean;
    created_at: string | null;
}

interface PaginationProps {
    data: CandidateRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export default function Index({ candidates }: { candidates: PaginationProps }) {
    const { hasPermission } = usePermissions();

    const handleDelete = (candidate: CandidateRow) => {
        router.delete(route('admin.candidates.destroy', candidate.id));
    };

    return (
        <AdminLayout>
            <Head title="Candidates" />

            <div className="mb-6 flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-900">Candidates</h2>
                {hasPermission('candidate.export') && (
                    <a
                        href={route('admin.candidates.export')}
                        className="rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        Export CSV
                    </a>
                )}
            </div>

            <div className="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                            <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Registered At</th>
                            {hasPermission('candidate.delete') && (
                                <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            )}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200 bg-white">
                        {candidates.data.length === 0 ? (
                            <tr>
                                <td colSpan={hasPermission('candidate.delete') ? 5 : 4} className="px-6 py-4 text-center text-sm text-gray-500">
                                    No candidates found
                                </td>
                            </tr>
                        ) : (
                            candidates.data.map((candidate) => (
                                <tr key={candidate.id}>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {candidate.name ?? <span className="text-gray-400">Unnamed</span>}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{candidate.email}</td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm">
                                        {candidate.is_guest ? (
                                            <span className="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold text-yellow-800">Guest</span>
                                        ) : (
                                            <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold text-green-800">Registered</span>
                                        )}
                                    </td>
                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {candidate.created_at ?? <span className="text-gray-400">Unknown</span>}
                                    </td>
                                    {hasPermission('candidate.delete') && (
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm">
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <button 
                                                        className="text-red-600 hover:text-red-900 cursor-pointer"
                                                    >
                                                        Delete
                                                    </button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete Candidate?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Are you sure you want to delete {candidate.name || candidate.email}? This action cannot be undone and all their assessment history will be lost.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction 
                                                            variant="destructive"
                                                            onClick={() => handleDelete(candidate)}
                                                        >
                                                            Delete Candidate
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </td>
                                    )}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            
            {/* Simple Pagination Footer Placeholder */}
            {candidates.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 rounded-xl ring-1 ring-gray-200">
                    <p className="text-sm text-gray-700">
                        Showing page <span className="font-medium">{candidates.current_page}</span> of <span className="font-medium">{candidates.last_page}</span> 
                        (Total: {candidates.total})
                    </p>
                </div>
            )}
        </AdminLayout>
    );
}
