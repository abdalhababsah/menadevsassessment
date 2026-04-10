<?php

namespace App\Models;

use App\Enums\RlhfTurnGenerationStatus;
use Database\Factories\RlhfGenerationJobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property RlhfTurnGenerationStatus $status
 */
class RlhfGenerationJob extends Model
{
    /** @use HasFactory<RlhfGenerationJobFactory> */
    use HasFactory;

    protected $fillable = [
        'rlhf_turn_id',
        'side',
        'status',
        'attempts',
        'last_error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RlhfTurnGenerationStatus::class,
            'attempts' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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
