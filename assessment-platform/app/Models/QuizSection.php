<?php

namespace App\Models;

use Database\Factories\QuizSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSection extends Model
{
    /** @use HasFactory<QuizSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'title',
        'description',
        'time_limit_seconds',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'time_limit_seconds' => 'integer',
            'position' => 'integer',
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
     * @return BelongsToMany<Question, $this, QuizSectionQuestion>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'quiz_section_questions')
            ->using(QuizSectionQuestion::class)
            ->withPivot(['question_version', 'points_override', 'time_limit_override_seconds', 'position'])
            ->orderByPivot('position');
    }

    /**
     * @return HasMany<QuizSectionQuestion, $this>
     */
    public function sectionQuestions(): HasMany
    {
        return $this->hasMany(QuizSectionQuestion::class)->orderBy('position');
    }
}
