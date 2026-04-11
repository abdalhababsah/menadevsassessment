<?php

namespace App\Http\Controllers\Api;

use App\Actions\Attempts\RecordSuspiciousEventAction;
use App\Enums\SuspiciousEventType;
use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuspiciousEventController extends Controller
{
    public function store(Request $request, RecordSuspiciousEventAction $action): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:'.implode(',', array_column(SuspiciousEventType::cases(), 'value'))],
            'metadata' => ['nullable', 'array'],
        ]);

        /** @var QuizAttempt $attempt */
        $attempt = $request->attributes->get('quizAttempt');

        $eventType = SuspiciousEventType::from($validated['event_type']);
        $event = $action->handle($attempt, $eventType, $validated['metadata'] ?? null);

        $attempt->refresh();

        return response()->json([
            'recorded' => true,
            'attempt_status' => $attempt->status->value,
        ], 201);
    }
}
