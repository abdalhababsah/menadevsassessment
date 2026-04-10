<?php

namespace App\Models;

use Database\Factories\AttemptAnswerSelectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswerSelection extends Model
{
    /** @use HasFactory<AttemptAnswerSelectionFactory> */
    use HasFactory;

    protected $fillable = [
        'attempt_answer_id',
        'question_option_id',
    ];

    /**
     * @return BelongsTo<AttemptAnswer, $this>
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(AttemptAnswer::class, 'attempt_answer_id');
    }

    /**
     * @return BelongsTo<QuestionOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
