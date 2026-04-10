<?php

namespace App\Models;

use Database\Factories\AttemptRlhfReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptRlhfReview extends Model
{
    /** @use HasFactory<AttemptRlhfReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'attempt_answer_id',
        'reviewer_id',
        'score',
        'decision',
        'comments',
        'finalized',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'finalized' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
