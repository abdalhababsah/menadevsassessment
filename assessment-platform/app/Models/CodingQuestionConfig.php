<?php

namespace App\Models;

use Database\Factories\CodingQuestionConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodingQuestionConfig extends Model
{
    /** @use HasFactory<CodingQuestionConfigFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'allowed_languages',
        'starter_code',
        'time_limit_ms',
        'memory_limit_mb',
    ];

    protected function casts(): array
    {
        return [
            'allowed_languages' => 'array',
            'starter_code' => 'array',
            'time_limit_ms' => 'integer',
            'memory_limit_mb' => 'integer',
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
