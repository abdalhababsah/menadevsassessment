<?php

namespace App\Models;

use App\Enums\RlhfScaleType;
use Database\Factories\RlhfCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property RlhfScaleType $scale_type
 * @property array<int|string, string> $scale_labels
 * @property array<int, int|string> $justification_required_when
 */
class RlhfCriterion extends Model
{
    /** @use HasFactory<RlhfCriterionFactory> */
    use HasFactory;

    protected $table = 'rlhf_criteria';

    protected $fillable = [
        'question_id',
        'name',
        'description',
        'scale_type',
        'scale_labels',
        'justification_required_when',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'scale_type' => RlhfScaleType::class,
            'scale_labels' => 'array',
            'justification_required_when' => 'array',
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
