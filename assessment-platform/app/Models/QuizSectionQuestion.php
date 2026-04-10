<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class QuizSectionQuestion extends Pivot
{
    protected $table = 'quiz_section_questions';

    public $incrementing = true;

    protected $fillable = [
        'quiz_section_id',
        'question_id',
        'question_version',
        'points_override',
        'time_limit_override_seconds',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'question_version' => 'integer',
            'points_override' => 'decimal:2',
            'time_limit_override_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<QuizSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(QuizSection::class, 'quiz_section_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
