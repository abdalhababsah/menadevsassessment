<?php

namespace App\Models;

use App\Enums\AnswerStatus;
use Database\Factories\AttemptAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttemptAnswer extends Model
{
    /** @use HasFactory<AttemptAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'question_version',
        'answered_at',
        'time_spent_seconds',
        'auto_score',
        'reviewer_score',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => AnswerStatus::class,
            'answered_at' => 'datetime',
            'time_spent_seconds' => 'integer',
            'question_version' => 'integer',
            'auto_score' => 'decimal:2',
            'reviewer_score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return HasMany<AttemptAnswerSelection, $this>
     */
    public function selections(): HasMany
    {
        return $this->hasMany(AttemptAnswerSelection::class);
    }

    /**
     * @return HasOne<AttemptCodingSubmission, $this>
     */
    public function codingSubmission(): HasOne
    {
        return $this->hasOne(AttemptCodingSubmission::class);
    }

    /**
     * @return HasMany<AttemptRlhfTurn, $this>
     */
    public function rlhfTurns(): HasMany
    {
        return $this->hasMany(AttemptRlhfTurn::class)->orderBy('turn_number');
    }

    /**
     * @return HasOne<AttemptRlhfReview, $this>
     */
    public function rlhfReview(): HasOne
    {
        return $this->hasOne(AttemptRlhfReview::class);
    }
}
