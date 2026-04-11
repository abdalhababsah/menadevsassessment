<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttemptCameraSnapshot;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CameraSnapshotController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'snapshot' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        /** @var QuizAttempt $attempt */
        $attempt = $request->attributes->get('quizAttempt');

        $file = $request->file('snapshot');
        $path = 'camera-snapshots/'.$attempt->id.'/'.Str::uuid().'.'.$file->extension();

        Storage::disk('local')->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        $snapshot = AttemptCameraSnapshot::create([
            'quiz_attempt_id' => $attempt->id,
            'url' => $path,
            'captured_at' => now(),
            'flagged' => false,
        ]);

        return response()->json([
            'id' => $snapshot->id,
            'captured_at' => $snapshot->captured_at?->toIso8601String(),
        ], 201);
    }
}
