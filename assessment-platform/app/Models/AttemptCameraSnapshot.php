<?php

namespace App\Models;

use Database\Factories\AttemptCameraSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $quiz_attempt_id
 * @property string $url
 * @property Carbon|null $captured_at
 * @property bool $flagged
 */
class AttemptCameraSnapshot extends Model
{
    /** @use HasFactory<AttemptCameraSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'url',
        'captured_at',
        'flagged',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'flagged' => 'boolean',
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
