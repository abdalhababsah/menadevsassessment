<?php

namespace App\Models;

use Database\Factories\AttemptCodingSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
