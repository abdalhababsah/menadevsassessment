<?php

namespace App\Models;

use Database\Factories\CodingTestCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodingTestCase extends Model
{
    /** @use HasFactory<CodingTestCaseFactory> */
    use HasFactory;

    protected $fillable = [
        'question_id',
        'input',
        'expected_output',
        'is_hidden',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'is_hidden' => 'boolean',
            'weight' => 'decimal:2',
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
