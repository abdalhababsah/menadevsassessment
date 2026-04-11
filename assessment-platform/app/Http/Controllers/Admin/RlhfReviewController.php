<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Rlhf\Review\FinalizeRlhfReviewAction;
use App\Actions\Rlhf\Review\SubmitRlhfReviewAction;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfEvaluation;
use App\Models\AttemptRlhfFormResponse;
use App\Models\AttemptRlhfTurn;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RlhfReviewController extends Controller
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function show(AttemptAnswer $attemptAnswer): Response
    {
        $attemptAnswer->load([
            'question.rlhfConfig',
            'question.rlhfCriteria',
            'question.rlhfFormFields',
            'attempt.candidate',
            'attempt.quiz',
            'rlhfReview.reviewer',
            'rlhfTurns.evaluations',
            'rlhfTurns.formResponses',
        ]);

        abort_unless(
            $attemptAnswer->question?->type === QuestionType::Rlhf,
            404,
            'Answer is not an RLHF answer.',
        );

        /** @var array<int, array<string, mixed>> $turns */
        $turns = [];
        foreach ($attemptAnswer->rlhfTurns->sortBy('turn_number') as $turn) {
            /** @var AttemptRlhfTurn $turn */
            $turns[] = $this->serializeTurn($turn);
        }

        /** @var array<int, array<string, mixed>> $criteria */
        $criteria = [];
        foreach ($attemptAnswer->question->rlhfCriteria->sortBy('position') as $criterion) {
            $criteria[] = [
                'id' => $criterion->id,
                'name' => $criterion->name,
                'description' => $criterion->description,
                'scale_labels' => $criterion->scale_labels,
            ];
        }

        return Inertia::render('Admin/Results/RlhfReview', [
            'answer' => [
                'id' => $attemptAnswer->id,
                'status' => $attemptAnswer->status->value,
                'question_stem' => $attemptAnswer->question->stem,
                'question_points' => (float) $attemptAnswer->question->points,
            ],
            'attempt' => [
                'id' => $attemptAnswer->attempt->id,
                'status' => $attemptAnswer->attempt->status->value,
            ],
            'quiz' => [
                'id' => $attemptAnswer->attempt->quiz->id,
                'title' => $attemptAnswer->attempt->quiz->title,
            ],
            'candidate' => [
                'name' => $attemptAnswer->attempt->candidate?->name,
                'email' => $attemptAnswer->attempt->candidate?->email,
            ],
            'criteria' => $criteria,
            'turns' => $turns,
            'review' => $attemptAnswer->rlhfReview ? [
                'id' => $attemptAnswer->rlhfReview->id,
                'score' => (float) $attemptAnswer->rlhfReview->score,
                'decision' => $attemptAnswer->rlhfReview->decision,
                'comments' => $attemptAnswer->rlhfReview->comments,
                'finalized' => (bool) $attemptAnswer->rlhfReview->finalized,
                'reviewer_name' => $attemptAnswer->rlhfReview->reviewer?->name,
            ] : null,
            'permissions' => [
                'can_score' => Auth::user()?->hasPermissionTo('rlhf.score') ?? false,
                'can_finalize' => Auth::user()?->hasPermissionTo('rlhf.finalize') ?? false,
            ],
        ]);
    }

    public function store(
        Request $request,
        AttemptAnswer $attemptAnswer,
        SubmitRlhfReviewAction $action,
    ): JsonResponse {
        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'decision' => ['required', 'string', 'in:accepted,partially_accepted,rejected'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ]);

        $review = $action->handle($attemptAnswer, $data);

        $this->auditLogger->log('rlhf.review_saved', $review, [
            'score' => $data['score'],
            'decision' => $data['decision'],
        ]);

        return response()->json([
            'saved' => true,
            'review' => [
                'id' => $review->id,
                'score' => (float) $review->score,
                'decision' => $review->decision,
                'comments' => $review->comments,
                'finalized' => (bool) $review->finalized,
            ],
        ]);
    }

    public function finalize(
        AttemptAnswer $attemptAnswer,
        FinalizeRlhfReviewAction $action,
    ): JsonResponse {
        $review = $action->handle($attemptAnswer);

        $this->auditLogger->log('rlhf.review_finalized', $review);

        $attempt = $attemptAnswer->attempt->refresh();

        return response()->json([
            'finalized' => true,
            'review' => [
                'id' => $review->id,
                'score' => (float) $review->score,
                'decision' => $review->decision,
                'finalized' => (bool) $review->finalized,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'final_score' => $attempt->final_score !== null ? (float) $attempt->final_score : null,
                'rlhf_review_status' => $attempt->rlhf_review_status->value,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTurn(AttemptRlhfTurn $turn): array
    {
        /** @var array<int, array<string, mixed>> $evaluations */
        $evaluations = [];
        foreach ($turn->evaluations as $evaluation) {
            /** @var AttemptRlhfEvaluation $evaluation */
            $evaluations[] = [
                'criterion_id' => $evaluation->criterion_id,
                'response_side' => $evaluation->response_side,
                'rating_value' => $evaluation->rating_value,
                'justification' => $evaluation->justification,
            ];
        }

        /** @var array<int, array<string, mixed>> $formResponses */
        $formResponses = [];
        foreach ($turn->formResponses as $formResponse) {
            /** @var AttemptRlhfFormResponse $formResponse */
            $formResponses[] = [
                'stage' => $formResponse->stage->value,
                'field_key' => $formResponse->field_key,
                'value' => $formResponse->value,
            ];
        }

        return [
            'id' => $turn->id,
            'turn_number' => $turn->turn_number,
            'candidate_input' => $turn->candidate_input,
            'response_a' => $turn->response_a,
            'response_b' => $turn->response_b,
            'model_a' => $turn->model_a,
            'model_b' => $turn->model_b,
            'sxs_rating' => $turn->sxs_rating,
            'sxs_justification' => $turn->sxs_justification,
            'selected_side' => $turn->selected_side?->value,
            'selected_response_rewrite' => $turn->selected_response_rewrite,
            'completed_at' => $turn->completed_at?->toIso8601String(),
            'evaluations' => $evaluations,
            'form_responses' => $formResponses,
        ];
    }
}
