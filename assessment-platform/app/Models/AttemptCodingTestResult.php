<?php

namespace App\Models;

use Database\Factories\AttemptCodingTestResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptCodingTestResult extends Model
{
    /** @use HasFactory<AttemptCodingTestResultFactory> */
    use HasFactory;

    protected $fillable = [
        'coding_submission_id',
        'test_case_id',
        'passed',
        'actual_output',
        'runtime_ms',
        'memory_kb',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'runtime_ms' => 'integer',
            'memory_kb' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AttemptCodingSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AttemptCodingSubmission::class, 'coding_submission_id');
    }

    /**
     * @return BelongsTo<CodingTestCase, $this>
     */
    public function testCase(): BelongsTo
    {
        return $this->belongsTo(CodingTestCase::class, 'test_case_id');
    }
}
