<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    public function index(): Response
    {
        $candidates = Candidate::query()
            ->latest()
            ->paginate(50)
            ->through(function (Candidate $candidate) {
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'email' => $candidate->email,
                    'is_guest' => $candidate->is_guest,
                    'created_at' => $candidate->created_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('Admin/Candidates/Index', [
            'candidates' => $candidates,
        ]);
    }

    public function export(): StreamedResponse
    {
        $headers = [
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=candidates_'.date('Y-m-d_H-i-s').'.csv',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email', 'Is Guest', 'Created At']);

            Candidate::query()->chunk(500, function ($candidates) use ($handle) {
                foreach ($candidates as $candidate) {
                    fputcsv($handle, [
                        $candidate->id,
                        $candidate->name,
                        $candidate->email,
                        $candidate->is_guest ? 'Yes' : 'No',
                        $candidate->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $candidate->delete();

        return redirect()->route('admin.candidates.index')
            ->with('success', 'Candidate deleted successfully.');
    }
}
