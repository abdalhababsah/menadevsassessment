<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_super_admin', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles {
        HasRoles::hasPermissionTo as traitHasPermissionTo;
    }

    use Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<UserInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class);
    }

    /**
     * Short-circuit permission checks for super admins.
     *
     * @param  string|Permission  $permission
     */
    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->traitHasPermissionTo($permission, $guardName);
    }
}
