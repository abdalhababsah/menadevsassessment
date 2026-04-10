<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use App\Enums\RlhfReviewStatus;
use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property AttemptStatus $status
 * @property RlhfReviewStatus $rlhf_review_status
 * @property Carbon $started_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $section_started_at
 * @property Carbon|null $question_started_at
 */
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'candidate_id',
        'invitation_id',
        'current_section_id',
        'current_question_id',
        'started_at',
        'submitted_at',
        'section_started_at',
        'question_started_at',
        'status',
        'auto_score',
        'final_score',
        'rlhf_review_status',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'rlhf_review_status' => RlhfReviewStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'section_started_at' => 'datetime',
            'question_started_at' => 'datetime',
            'auto_score' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * @return BelongsTo<QuizInvitation, $this>
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(QuizInvitation::class);
    }

    /**
     * @return BelongsTo<QuizSection, $this>
     */
    public function currentSection(): BelongsTo
    {
        return $this->belongsTo(QuizSection::class, 'current_section_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function currentQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'current_question_id');
    }

    /**
     * @return HasMany<AttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    /**
     * @return HasMany<AttemptSuspiciousEvent, $this>
     */
    public function suspiciousEvents(): HasMany
    {
        return $this->hasMany(AttemptSuspiciousEvent::class);
    }

    /**
     * @return HasMany<AttemptCameraSnapshot, $this>
     */
    public function cameraSnapshots(): HasMany
    {
        return $this->hasMany(AttemptCameraSnapshot::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === AttemptStatus::InProgress;
    }

    public function isComplete(): bool
    {
        return in_array($this->status, [
            AttemptStatus::Submitted,
            AttemptStatus::AutoSubmitted,
        ]);
    }
}
