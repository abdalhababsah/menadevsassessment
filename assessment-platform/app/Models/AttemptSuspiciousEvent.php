<?php

namespace App\Models;

use App\Enums\SuspiciousEventType;
use Database\Factories\AttemptSuspiciousEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptSuspiciousEvent extends Model
{
    /** @use HasFactory<AttemptSuspiciousEventFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'event_type',
        'occurred_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => SuspiciousEventType::class,
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }
}
