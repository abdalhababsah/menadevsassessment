<?php

namespace App\Models;

use App\Enums\MediaType;
use Database\Factories\QuestionMediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionMedia extends Model
{
    /** @use HasFactory<QuestionMediaFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'media_type',
        'url',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'media_type' => MediaType::class,
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
