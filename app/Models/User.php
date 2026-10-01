<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Support\MediaUrl;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $phone
 * @property string $locale
 * @property bool $is_blocked
 * @property Carbon|null $blocked_at
 * @property string|null $blocked_reason
 * @property int|null $ai_monthly_token_limit
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read AuthorProfile|null $authorProfile
 * @property-read Collection<int, Article> $submittedArticles
 * @property-read Collection<int, Payment> $payments
 */
#[Fillable(['name', 'email', 'password', 'phone', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

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
            'two_factor_confirmed_at' => 'datetime',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'ai_monthly_token_limit' => 'integer',
            'last_login_at' => 'datetime',
        ];
    }

    /** @return HasOne<AuthorProfile, $this> */
    public function authorProfile(): HasOne
    {
        return $this->hasOne(AuthorProfile::class);
    }

    /**
     * Foydalanuvchi yuborgan maqolalar.
     *
     * @return HasMany<Article, $this>
     */
    public function submittedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'submitter_id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<AiRequest, $this> */
    public function aiRequests(): HasMany
    {
        return $this->hasMany(AiRequest::class);
    }

    /** Profil rasmi URL'i (author_profiles.avatar_path) yoki null */
    public function avatarUrl(): ?string
    {
        return MediaUrl::from($this->authorProfile?->avatar_path);
    }

    /** Admin panelga kira oladigan xodimmi (muallifdan boshqa istalgan rol) */
    public function isStaff(): bool
    {
        return $this->hasAnyRole(RoleName::staff());
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin);
    }

    /** Login'dan keyin qaysi qismga yo'naltirilishi */
    public function homeRouteName(): string
    {
        return $this->isStaff() ? 'admin.dashboard' : 'cabinet.dashboard';
    }

    public function block(?string $reason = null): void
    {
        $this->forceFill([
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => $reason,
        ])->save();
    }

    public function unblock(): void
    {
        $this->forceFill([
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
        ])->save();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeStaff(Builder $query): Builder
    {
        return $query->role(RoleName::staffValues());
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_blocked', false);
    }
}
