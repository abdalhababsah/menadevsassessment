<?php

namespace App\Models;

use Database\Factories\AttemptCodingSubmissionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $attempt_answer_id
 * @property string $language
 * @property string $code
 * @property Carbon|null $submitted_at
 * @property-read AttemptAnswer|null $answer
 * @property-read Collection<int, AttemptCodingTestResult> $testResults
 */
class AttemptCodingSubmission extends Model
{
    /** @use HasFactory<AttemptCodingSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'attempt_answer_id',
        'language',
        'code',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
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
     * @return HasMany<AttemptCodingTestResult, $this>
     */
    public function testResults(): HasMany
    {
        return $this->hasMany(AttemptCodingTestResult::class, 'coding_submission_id');
    }
}
