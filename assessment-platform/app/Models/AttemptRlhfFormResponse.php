<?php

namespace App\Models;

use App\Enums\RlhfFormStage;
use Database\Factories\AttemptRlhfFormResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptRlhfFormResponse extends Model
{
    /** @use HasFactory<AttemptRlhfFormResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'rlhf_turn_id',
        'stage',
        'field_key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'stage' => RlhfFormStage::class,
        ];
    }

    /**
     * @return BelongsTo<AttemptRlhfTurn, $this>
     */
    public function turn(): BelongsTo
    {
        return $this->belongsTo(AttemptRlhfTurn::class, 'rlhf_turn_id');
    }
}
