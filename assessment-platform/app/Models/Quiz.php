<?php

namespace App\Models;

use App\Enums\QuizNavigationMode;
use App\Enums\QuizStatus;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property QuizStatus $status
 * @property QuizNavigationMode $navigation_mode
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'time_limit_seconds',
        'passing_score',
        'randomize_questions',
        'randomize_options',
        'navigation_mode',
        'camera_enabled',
        'anti_cheat_enabled',
        'max_fullscreen_exits',
        'starts_at',
        'ends_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuizStatus::class,
            'navigation_mode' => QuizNavigationMode::class,
            'passing_score' => 'decimal:2',
            'time_limit_seconds' => 'integer',
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
            'camera_enabled' => 'boolean',
            'anti_cheat_enabled' => 'boolean',
            'max_fullscreen_exits' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<QuizSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(QuizSection::class)->orderBy('position');
    }

    /**
     * @return HasMany<QuizInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(QuizInvitation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', QuizStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', QuizStatus::Published)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }
}
