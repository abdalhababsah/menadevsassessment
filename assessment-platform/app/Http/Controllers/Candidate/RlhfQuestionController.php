<?php

namespace App\Http\Controllers\Candidate;

use App\Actions\Rlhf\AdvanceRlhfTurnAction;
use App\Actions\Rlhf\StartRlhfTurnAction;
use App\Actions\Rlhf\SubmitRlhfEvaluationAction;
use App\Actions\Rlhf\SubmitRlhfFormResponseAction;
use App\Actions\Rlhf\SubmitRlhfPromptInputAction;
use App\Actions\Rlhf\SubmitRlhfRewriteAction;
use App\Actions\Rlhf\SubmitRlhfSxsRatingAction;
use App\Enums\AnswerStatus;
use App\Enums\QuestionType;
use App\Enums\RlhfFieldType;
use App\Enums\RlhfFormStage;
use App\Enums\SelectedSide;
use App\Http\Controllers\Controller;
use App\Models\AttemptAnswer;
use App\Models\AttemptRlhfTurn;
use App\Models\QuizAttempt;
use App\Services\Rlhf\RlhfRuntimeStateBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RlhfQuestionController extends Controller
{
    public function __construct(
        private RlhfRuntimeStateBuilder $stateBuilder,
    ) {}

    public function show(Request $request, StartRlhfTurnAction $startTurn): Response
    {
        $answer = $this->resolveCurrentAnswer($request);

        if ($answer->status !== AnswerStatus::Answered) {
            $this->ensureCurrentTurn($answer, $startTurn);
        }

        return Inertia::render('Candidate/Quiz/Rlhf/Runner', [
            'state' => $this->stateBuilder->build($answer->fresh()),
        ]);
    }

    public function submitFormResponse(
        Request $request,
        StartRlhfTurnAction $startTurn,
        SubmitRlhfFormResponseAction $action,
    ): JsonResponse {
        $answer = $this->resolveCurrentAnswer($request);
        $turn = $this->ensureCurrentTurn($answer, $startTurn);

        $data = $request->validate([
            'stage' => ['required', Rule::in(RlhfFormStage::values())],
            'responses' => ['required', 'array'],
        ]);

        $stage = RlhfFormStage::from($data['stage']);
        $expectedStep = match ($stage) {
            RlhfFormStage::PrePrompt => 'pre_prompt_form',
            RlhfFormStage::PostPrompt => 'post_prompt_form',
            RlhfFormStage::PostRewrite => 'post_rewrite_form',
        };
        $this->assertCurrentStep($answer, $turn, [$expectedStep], 'This RLHF form is not available right now.');
        $fields = $answer->question->rlhfFormFields
            ->where('stage', $stage)
            ->sortBy('position')
            ->values();

        $allowedFieldKeys = $fields->pluck('field_key')->all();

        foreach (array_keys($data['responses']) as $fieldKey) {
            if (! in_array($fieldKey, $allowedFieldKeys, true)) {
                throw ValidationException::withMessages([
                    'responses' => "Unknown RLHF field [{$fieldKey}] for stage [{$stage->value}].",
                ]);
            }
        }

        foreach ($fields as $field) {
            $value = $data['responses'][$field->field_key] ?? null;

            if ($field->required && $this->isEmptyFieldValue($field->field_type, $value)) {
                throw ValidationException::withMessages([
                    "responses.{$field->field_key}" => "The {$field->label} field is required.",
                ]);
            }

            if ($value === null || $value === '') {
                continue;
            }

            $this->validateFormFieldValue($field->field_key, $field->label, $field->field_type, $field->options, $field->min_length, $value);
        }

        $action->handle($turn, $stage, $data['responses']);

        return $this->stateResponse($answer);
    }

    public function submitPromptInput(
        Request $request,
        StartRlhfTurnAction $startTurn,
        SubmitRlhfPromptInputAction $action,
    ): JsonResponse {
        $answer = $this->resolveCurrentAnswer($request);
        $turn = $this->ensureCurrentTurn($answer, $startTurn);
        $this->assertCurrentStep($answer, $turn, ['prompt_input'], 'Prompt input is not available right now.');

        $data = $request->validate([
            'input' => ['required', 'string'],
            'audio_url' => ['nullable', 'string'],
        ]);

        $action->handle($turn, $data['input'], $data['audio_url'] ?? null);

        return $this->stateResponse($answer);
    }

    public function pollGenerationStatus(Request $request, StartRlhfTurnAction $startTurn): JsonResponse
    {
        $answer = $this->resolveCurrentAnswer($request);
        $turn = $this->ensureCurrentTurn($answer, $startTurn)->fresh(['generationJobs']);
        $state = $this->stateBuilder->build($answer->fresh());

        return response()->json([
            'state' => $state,
            'current_step' => $state['current_step'],
            'turn' => $state['current_turn'],
            'generation' => $state['current_turn']['generation'] ?? null,
            'responses_ready' => $turn->bothResponsesReady(),
        ]);
    }

    public function submitEvaluation(
        Request $request,
        StartRlhfTurnAction $startTurn,
        SubmitRlhfEvaluationAction $action,
    ): JsonResponse {
        $answer = $this->resolveCurrentAnswer($request);
        $turn = $this->ensureCurrentTurn($answer, $startTurn);

        $data = $request->validate([
            'response_side' => ['required', Rule::in(['a', 'b'])],
            'evaluations' => ['required', 'array', 'min:1'],
            'evaluations.*.criterion_id' => ['required', 'integer'],
            'evaluations.*.rating_value' => ['required', 'string'],
            'evaluations.*.justification' => ['nullable', 'string'],
        ]);
        $this->assertCurrentStep(
            $answer,
            $turn,
            [$data['response_side'] === 'a' ? 'evaluate_a' : 'evaluate_b'],
            'This response is not ready for evaluation yet.',
        );

        $criteria = $answer->question->rlhfCriteria
            ->sortBy('position')
            ->values();

        $expectedCriteriaIds = $criteria->pluck('id')->sort()->values()->all();

        $providedCriteriaIds = collect($data['evaluations'])
            ->pluck('criterion_id')
            ->map(fn (mixed $value) => (int) $value)
            ->sort()
            ->values()
            ->all();

        if ($expectedCriteriaIds !== $providedCriteriaIds) {
            throw ValidationException::withMessages([
                'evaluations' => 'Evaluations must include every criterion exactly once.',
            ]);
        }

        foreach ($criteria as $criterion) {
            $evaluation = collect($data['evaluations'])
                ->firstWhere('criterion_id', $criterion->id);

            if ($evaluation === null) {
                continue;
            }

            $allowedRatings = array_map('strval', array_keys($criterion->scale_labels));
            $ratingValue = (string) $evaluation['rating_value'];

            if (! in_array($ratingValue, $allowedRatings, true)) {
                throw ValidationException::withMessages([
                    'evaluations' => "Invalid rating [{$ratingValue}] for criterion [{$criterion->name}].",
                ]);
            }

            $justificationRequired = collect($criterion->justification_required_when)
                ->map(fn (mixed $value) => (string) $value)
                ->contains($ratingValue);

            if ($justificationRequired && blank($evaluation['justification'] ?? null)) {
                throw ValidationException::withMessages([
                    'evaluations' => "A justification is required for criterion [{$criterion->name}] when rating [{$ratingValue}] is selected.",
                ]);
            }
        }

        $action->handle($turn, $data['response_side'], $data['evaluations']);

        return $this->stateResponse($answer);
    }

    public function submitSxsRating(
        Request $request,
        StartRlhfTurnAction $startTurn,
        SubmitRlhfSxsRatingAction $action,
    ): JsonResponse {
        $answer = $this->resolveCurrentAnswer($request);
        $turn = $this->ensureCurrentTurn($answer, $startTurn);
        $this->assertCurrentStep($answer, $turn, ['sxs_rating'], 'Side-by-side rating is not available right now.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:7'],
            'justification' => ['required', 'string'],
            'selected_side' => ['nullable', Rule::in(SelectedSide::values())],
        ]);

        if (($data['rating'] < 4 && ($data['selected_side'] ?? 'a') !== SelectedSide::A->value)
            || ($data['rating'] > 4 && ($data['selected_side'] ?? 'b') !== SelectedSide::B->value)
            || ($data['rating'] === 4 && (($data['selected_side'] ?? null) !== null))
        ) {
            throw ValidationException::withMessages([
                'selected_side' => 'The selected side must align with the submitted side-by-side rating.',
            ]);
        }

        $action->handle(
            $turn,
            (int) $data['rating'],
            $data['justification'],
            isset($data['selected_side']) ? SelectedSide::from($data['selected_side']) : null,
        );

        return $this->stateResponse($answer);
    }

    public function submitRewrite(
        Request $request,
        StartRlhfTurnAction $startTurn,
        SubmitRlhfRewriteAction $action,
    ): JsonResponse {
        $answer = $this->resolveCurrentAnswer($request);
        $turn = $this->ensureCurrentTurn($answer, $startTurn);
        $this->assertCurrentStep($answer, $turn, ['rewrite'], 'Rewrite is not available right now.');

        $data = $request->validate([
            'rewrite' => ['required', 'string'],
        ]);

        $action->handle($turn, $data['rewrite']);

        return $this->stateResponse($answer);
    }

    public function advanceTurn(
        Request $request,
        StartRlhfTurnAction $startTurn,
        AdvanceRlhfTurnAction $action,
    ): JsonResponse {
        $answer = $this->resolveCurrentAnswer($request);
        $turn = $this->ensureCurrentTurn($answer, $startTurn)->fresh([
            'answer.question.rlhfConfig',
            'answer.question.rlhfCriteria',
            'answer.question.rlhfFormFields',
            'formResponses',
            'evaluations',
            'generationJobs',
        ]);
        $answer = $turn->answer;
        $currentStep = $this->stateBuilder->currentStep($answer, $turn);

        if ($currentStep !== 'turn_complete' && ! $this->turnCanAdvance($answer, $turn)) {
            throw ValidationException::withMessages([
                'turn' => 'The current turn is not ready to advance.',
            ]);
        }

        $action->handle($turn);

        return $this->stateResponse($answer->fresh());
    }

    private function resolveCurrentAnswer(Request $request): AttemptAnswer
    {
        /** @var QuizAttempt $attempt */
        $attempt = $request->attributes->get('quizAttempt')
            ?? QuizAttempt::query()->findOrFail((int) $request->session()->get('quiz_attempt_id', 0));

        $answer = AttemptAnswer::query()
            ->with([
                'attempt.quiz',
                'question.rlhfConfig',
                'question.rlhfCriteria',
                'question.rlhfFormFields',
                'rlhfTurns.generationJobs',
                'rlhfTurns.evaluations',
                'rlhfTurns.formResponses',
            ])
            ->where('quiz_attempt_id', $attempt->id)
            ->where('question_id', $attempt->current_question_id)
            ->firstOrFail();

        if ($answer->question->type !== QuestionType::Rlhf) {
            abort(404, 'The current question is not an RLHF question.');
        }

        return $answer;
    }

    private function ensureCurrentTurn(AttemptAnswer $answer, StartRlhfTurnAction $startTurn): AttemptRlhfTurn
    {
        $currentTurn = $this->stateBuilder->currentTurn($answer);

        if ($currentTurn !== null) {
            return $currentTurn;
        }

        return $startTurn->handle($answer);
    }

    private function stateResponse(AttemptAnswer $answer): JsonResponse
    {
        return response()->json([
            'state' => $this->stateBuilder->build($answer->fresh()),
        ]);
    }

    /**
     * @param  array<int, string>  $allowedSteps
     */
    private function assertCurrentStep(
        AttemptAnswer $answer,
        AttemptRlhfTurn $turn,
        array $allowedSteps,
        string $message,
    ): void {
        $currentStep = $this->stateBuilder->currentStep($answer, $turn);

        if (! in_array($currentStep, $allowedSteps, true)) {
            throw ValidationException::withMessages([
                'step' => $message,
            ]);
        }
    }

    /**
     * @param  array<int, string>|null  $options
     */
    private function validateFormFieldValue(
        string $fieldKey,
        string $label,
        RlhfFieldType $fieldType,
        ?array $options,
        ?int $minLength,
        mixed $value,
    ): void {
        $allowedOptions = array_map('strval', $options ?? []);

        if ($fieldType === RlhfFieldType::MultiSelect) {
            if (! is_array($value)) {
                throw ValidationException::withMessages([
                    "responses.{$fieldKey}" => "The {$label} field must be an array.",
                ]);
            }

            foreach ($value as $selectedValue) {
                if (! in_array((string) $selectedValue, $allowedOptions, true)) {
                    throw ValidationException::withMessages([
                        "responses.{$fieldKey}" => "The {$label} field contains an invalid option.",
                    ]);
                }
            }

            return;
        }

        if (! is_scalar($value)) {
            throw ValidationException::withMessages([
                "responses.{$fieldKey}" => "The {$label} field must be a scalar value.",
            ]);
        }

        $stringValue = trim((string) $value);

        if (in_array($fieldType, [RlhfFieldType::Radio, RlhfFieldType::Dropdown], true)
            && ! in_array($stringValue, $allowedOptions, true)
        ) {
            throw ValidationException::withMessages([
                "responses.{$fieldKey}" => "The {$label} field contains an invalid option.",
            ]);
        }

        if (in_array($fieldType, [RlhfFieldType::Text, RlhfFieldType::Textarea], true)
            && $minLength !== null
            && mb_strlen($stringValue) < $minLength
        ) {
            throw ValidationException::withMessages([
                "responses.{$fieldKey}" => "The {$label} field must be at least {$minLength} characters.",
            ]);
        }
    }

    private function isEmptyFieldValue(RlhfFieldType $fieldType, mixed $value): bool
    {
        if ($fieldType === RlhfFieldType::MultiSelect) {
            return ! is_array($value) || $value === [];
        }

        return blank($value);
    }

    private function turnCanAdvance(AttemptAnswer $answer, AttemptRlhfTurn $turn): bool
    {
        $answer->loadMissing([
            'question.rlhfConfig',
            'question.rlhfCriteria',
            'question.rlhfFormFields',
        ]);
        $turn->loadMissing(['formResponses', 'evaluations']);

        $config = $answer->question->rlhfConfig;
        $criteriaCount = $answer->question->rlhfCriteria->count();

        if (($config?->enable_pre_prompt_form ?? false)
            && ! $this->requiredStageFieldsAreComplete($answer, $turn, RlhfFormStage::PrePrompt)
        ) {
            return false;
        }

        if (blank($turn->candidate_input) || ! $turn->bothResponsesReady()) {
            return false;
        }

        if (($config?->enable_post_prompt_form ?? false)
            && ! $this->requiredStageFieldsAreComplete($answer, $turn, RlhfFormStage::PostPrompt)
        ) {
            return false;
        }

        if ($turn->evaluations->where('response_side', 'a')->count() < $criteriaCount
            || $turn->evaluations->where('response_side', 'b')->count() < $criteriaCount
        ) {
            return false;
        }

        if ($turn->sxs_rating === null) {
            return false;
        }

        if (($config?->enable_rewrite_step ?? false)
            && $turn->selected_side !== null
            && blank($turn->selected_response_rewrite)
        ) {
            return false;
        }

        if (($config?->enable_post_rewrite_form ?? false)
            && ! $this->requiredStageFieldsAreComplete($answer, $turn, RlhfFormStage::PostRewrite)
        ) {
            return false;
        }

        return true;
    }

    private function requiredStageFieldsAreComplete(
        AttemptAnswer $answer,
        AttemptRlhfTurn $turn,
        RlhfFormStage $stage,
    ): bool {
        $fields = $answer->question->rlhfFormFields
            ->where('stage', $stage)
            ->where('required', true);

        foreach ($fields as $field) {
            $response = $turn->formResponses
                ->where('stage', $stage)
                ->firstWhere('field_key', $field->field_key);

            if ($response === null) {
                return false;
            }

            $value = json_decode((string) $response->value, true);
            $storedValue = json_last_error() === JSON_ERROR_NONE ? $value : $response->value;

            if ($this->isEmptyFieldValue($field->field_type, $storedValue)) {
                return false;
            }
        }

        return true;
    }
}
