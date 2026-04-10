<?php

namespace App\Models;

use App\Enums\RlhfFieldType;
use App\Enums\RlhfFormStage;
use Database\Factories\RlhfQuestionFormFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property RlhfFormStage $stage
 * @property RlhfFieldType $field_type
 * @property array<int, string>|null $options
 */
class RlhfQuestionFormField extends Model
{
    /** @use HasFactory<RlhfQuestionFormFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'stage',
        'field_key',
        'label',
        'description',
        'field_type',
        'options',
        'required',
        'min_length',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'stage' => RlhfFormStage::class,
            'field_type' => RlhfFieldType::class,
            'options' => 'array',
            'required' => 'boolean',
            'min_length' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
