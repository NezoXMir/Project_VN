<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'color',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Категории, доступные пользователю: системные (user_id = null)
     * + его собственные.
     */
    public function scopeAvailableTo(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('is_system', true)
                ->orWhere('user_id', $userId);
        });
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isOwnedBy(int $userId): bool
    {
        return ! $this->is_system && $this->user_id === $userId;
    }
}
