<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'status',
        'deadline',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    protected function progress(): Attribute
    {
        return Attribute::get(function (): int {
            if (! $this->relationLoaded('subtasks') && ! \Schema::hasTable('tasks')) {
                return 0;
            }

            $tasks = $this->subtasks->flatMap->tasks ?? collect();
            $total = $tasks->count();

            if ($total === 0) {
                return 0;
            }

            $done = $tasks->where('is_done', true)->count();

            return (int) round($done / $total * 100);
        });
    }
}
