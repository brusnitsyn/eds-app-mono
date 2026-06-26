<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasPdnEncryption;
use App\Support\PasswordPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasPdnEncryption;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'login',
        'name',
        'email',
        'password',
        'phone',
        'role_id'
    ];

    /**
     * Поля ПДн, шифруемые на уровне приложения (мера ЗНИ).
     *
     * @var array<int, string>
     */
    protected array $encrypted = ['login', 'email', 'name'];

    /**
     * Карта «зашифрованный атрибут → колонка детерминированного хеша».
     *
     * @var array<string, string>
     */
    protected array $pdnLookupColumns = [
        'login' => 'login_hash',
        'email' => 'email_hash',
    ];

    /**
     * login/email значимы посимвольно (дефис/пробел не нормализуются) —
     * строгий хеш во избежание ложных совпадений разных значений.
     *
     * @var array<int, string>
     */
    protected array $pdnExactLookupColumns = ['login', 'email'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

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
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_blocked' => 'boolean',
        ];
    }

    protected function defaultProfilePhotoUrl(): string
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=18a058&background=e7f5ee&bold=true&uppercase=true&format=svg';
    }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasMany<PasswordHistory, $this>
     */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    /**
     * Истёк ли срок действия текущего пароля (мера ИАФ.3).
     */
    public function passwordExpired(): bool
    {
        $maxAge = PasswordPolicy::maxAgeDays();

        if ($maxAge <= 0) {
            return false;
        }

        $changedAt = $this->password_changed_at ?? $this->created_at;

        return $changedAt !== null && $changedAt->addDays($maxAge)->isPast();
    }

    /**
     * Заблокирована ли учётная запись (мера УПД.1, УПД.6).
     */
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    /**
     * Есть ли у роли пользователя указанный scope (мера УПД.2/УПД.5).
     */
    public function hasScope(string $scope): bool
    {
        return $this->role?->scopes->pluck('name')->contains($scope) ?? false;
    }
}
