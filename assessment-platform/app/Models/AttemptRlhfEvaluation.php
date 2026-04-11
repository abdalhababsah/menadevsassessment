<?php

namespace App\Models;

use Database\Factories\AttemptRlhfEvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rlhf_turn_id
 * @property int $criterion_id
 * @property string $response_side
 * @property string $rating_value
 * @property string|null $justification
 */
class AttemptRlhfEvaluation extends Model
{
    /** @use HasFactory<AttemptRlhfEvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'rlhf_turn_id',
        'criterion_id',
        'response_side',
        'rating_value',
        'justification',
    ];

    /**
     * @return BelongsTo<AttemptRlhfTurn, $this>
     */
    public function turn(): BelongsTo
    {
        return $this->belongsTo(AttemptRlhfTurn::class, 'rlhf_turn_id');
    }

    /**
     * @return BelongsTo<RlhfCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RlhfCriterion::class, 'criterion_id');
    }
}
