<?php

namespace App\Models;

use Database\Factories\RlhfQuestionConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RlhfQuestionConfig extends Model
{
    /** @use HasFactory<RlhfQuestionConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'number_of_turns',
        'candidate_input_mode',
        'model_a',
        'model_b',
        'generation_params',
        'enable_pre_prompt_form',
        'enable_post_prompt_form',
        'enable_rewrite_step',
        'enable_post_rewrite_form',
        'guidelines_markdown',
    ];

    protected function casts(): array
    {
        return [
            'number_of_turns' => 'integer',
            'generation_params' => 'array',
            'enable_pre_prompt_form' => 'boolean',
            'enable_post_prompt_form' => 'boolean',
            'enable_rewrite_step' => 'boolean',
            'enable_post_rewrite_form' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return HasMany<RlhfCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(RlhfCriterion::class, 'question_id', 'question_id');
    }

    /**
     * @return HasMany<RlhfQuestionFormField, $this>
     */
    public function formFields(): HasMany
    {
        return $this->hasMany(RlhfQuestionFormField::class, 'question_id', 'question_id');
    }
}
