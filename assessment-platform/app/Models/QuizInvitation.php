<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Database\Factories\QuizInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class QuizInvitation extends Model
{
    /** @use HasFactory<QuizInvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'token',
        'max_uses',
        'uses_count',
        'expires_at',
        'email_domain_restriction',
        'created_by',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuizInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
        });
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        /** @var Carbon $expiresAt */
        $expiresAt = $this->expires_at;

        return $expiresAt->isPast();
    }

    public function isExhausted(): bool
    {
        if ($this->max_uses === null) {
            return false;
        }

        return $this->uses_count >= $this->max_uses;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired() && ! $this->isExhausted() && ! $this->isRevoked();
    }

    public function status(): InvitationStatus
    {
        if ($this->isRevoked()) {
            return InvitationStatus::Revoked;
        }

        if ($this->isExpired()) {
            return InvitationStatus::Expired;
        }

        if ($this->isExhausted()) {
            return InvitationStatus::Exhausted;
        }

        return InvitationStatus::Active;
    }
}
