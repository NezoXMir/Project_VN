<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_path',
        'bio',
        'email_reminders_enabled',
        'blocked_at',
        'email_verified_at',
        'email_verification_code',
        'email_verification_expires_at',
    ];

    public const ROLE_USER = 'user';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_ADMIN = 'admin';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    /**
     * Любой представитель административного интерфейса (admin или manager).
     * Используется в middleware/policy чтобы не перечислять оба значения.
     */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_ADMIN
            || $this->role === self::ROLE_MANAGER;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /** Человекочитаемая подпись роли — для админ-таблиц и бейджей. */
    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Администратор',
            self::ROLE_MANAGER => 'Менеджер',
            default => 'Пользователь',
        };
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps()
            ->orderByPivot('unlocked_at', 'desc');
    }

    /**
     * Относительный URL аватара или null, если не загружен.
     * Возвращаем именно относительный путь (а не Storage::url()),
     * чтобы не зависеть от APP_URL — `php artisan serve` может
     * слушать на другом порту, чем прописано в env.
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path
            ? '/storage/'.$this->avatar_path
            : null;
    }

    /**
     * Первая буква имени для fallback-аватара. На пустом name
     * возвращает «?», чтобы не было пустого кружка.
     */
    public function initial(): string
    {
        $name = trim((string) $this->name);
        if ($name === '') {
            return '?';
        }
        return mb_strtoupper(mb_substr($name, 0, 1));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'              => 'datetime',
            'email_verification_expires_at'  => 'datetime',
            'password'                       => 'hashed',
            'email_reminders_enabled'        => 'boolean',
            'blocked_at'                     => 'datetime',
        ];
    }
}
