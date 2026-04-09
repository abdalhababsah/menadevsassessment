<?php

namespace App\Models;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'stem',
        'instructions',
        'difficulty',
        'points',
        'time_limit_seconds',
        'version',
        'parent_question_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'difficulty' => QuestionDifficulty::class,
            'points' => 'decimal:2',
            'time_limit_seconds' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return HasMany<QuestionMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(QuestionMedia::class)->orderBy('position');
    }

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    /**
     * @return HasOne<CodingQuestionConfig, $this>
     */
    public function codingConfig(): HasOne
    {
        return $this->hasOne(CodingQuestionConfig::class);
    }

    /**
     * @return HasMany<CodingTestCase, $this>
     */
    public function testCases(): HasMany
    {
        return $this->hasMany(CodingTestCase::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_question_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_question_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOfType(Builder $query, QuestionType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeByDifficulty(Builder $query, QuestionDifficulty $difficulty): Builder
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<int>  $tagIds
     * @return Builder<self>
     */
    public function scopeWithTags(Builder $query, array $tagIds): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tagIds) {
            $q->whereIn('tags.id', $tagIds);
        });
    }
}
