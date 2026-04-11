<?php

namespace App\Models;

use App\Enums\RlhfTurnGenerationStatus;
use App\Enums\SelectedSide;
use Database\Factories\AttemptRlhfTurnFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $attempt_answer_id
 * @property int $turn_number
 * @property string|null $candidate_input
 * @property string|null $candidate_input_audio_url
 * @property string|null $response_a
 * @property string|null $response_b
 * @property string $model_a
 * @property string $model_b
 * @property RlhfTurnGenerationStatus $generation_status
 * @property string|null $generation_error
 * @property Carbon|null $generated_at
 * @property int|null $sxs_rating
 * @property string|null $sxs_justification
 * @property SelectedSide|null $selected_side
 * @property string|null $selected_response_rewrite
 * @property Carbon|null $rewrite_completed_at
 * @property Carbon|null $completed_at
 * @property-read Collection<int, AttemptRlhfEvaluation> $evaluations
 * @property-read Collection<int, AttemptRlhfFormResponse> $formResponses
 */
class AttemptRlhfTurn extends Model
{
    /** @use HasFactory<AttemptRlhfTurnFactory> */
    use HasFactory;

    protected $fillable = [
        'attempt_answer_id',
        'turn_number',
        'candidate_input',
        'candidate_input_audio_url',
        'response_a',
        'response_b',
        'model_a',
        'model_b',
        'generation_status',
        'generation_error',
        'generated_at',
        'sxs_rating',
        'sxs_justification',
        'selected_side',
        'selected_response_rewrite',
        'rewrite_completed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'turn_number' => 'integer',
            'generation_status' => RlhfTurnGenerationStatus::class,
            'generated_at' => 'datetime',
            'sxs_rating' => 'integer',
            'selected_side' => SelectedSide::class,
            'rewrite_completed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AttemptAnswer, $this>
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(AttemptAnswer::class, 'attempt_answer_id');
    }

    /**
     * @return HasMany<AttemptRlhfEvaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(AttemptRlhfEvaluation::class, 'rlhf_turn_id');
    }

    /**
     * @return HasMany<AttemptRlhfFormResponse, $this>
     */
    public function formResponses(): HasMany
    {
        return $this->hasMany(AttemptRlhfFormResponse::class, 'rlhf_turn_id');
    }

    /**
     * @return HasMany<RlhfGenerationJob, $this>
     */
    public function generationJobs(): HasMany
    {
        return $this->hasMany(RlhfGenerationJob::class, 'rlhf_turn_id');
    }

    public function bothResponsesReady(): bool
    {
        return $this->response_a !== null && $this->response_b !== null;
    }

    public function selectedResponseText(): ?string
    {
        return match ($this->selected_side) {
            SelectedSide::A => $this->response_a,
            SelectedSide::B => $this->response_b,
            default => null,
        };
    }

    /**
     * @return Collection<int, self>
     */
    public function priorTurns(): Collection
    {
        return self::where('attempt_answer_id', $this->attempt_answer_id)
            ->where('turn_number', '<', $this->turn_number)
            ->orderBy('turn_number')
            ->get();
    }
}
