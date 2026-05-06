<?php

namespace App\Services;

use App\Models\Category;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Все категории, доступные пользователю: системные первыми
     * (по id), потом его собственные (свежие сверху).
     */
    public function listAvailable(int $userId): Collection
    {
        return Category::query()
            ->availableTo($userId)
            ->orderByDesc('is_system')
            ->orderBy('id')
            ->orderByDesc('created_at')
            ->get();
    }

    public function listOwn(int $userId): Collection
    {
        return Category::query()
            ->ownedBy($userId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Уникальные hex-цвета, которые пользователь уже использовал в своих
     * категориях. Для секции «Твои цвета» в форме создания/редактирования.
     */
    public function userColorsFor(int $userId): array
    {
        return Category::query()
            ->ownedBy($userId)
            ->orderByDesc('created_at')
            ->pluck('color')
            ->unique()
            ->values()
            ->all();
    }

    public function create(int $userId, array $data): Category
    {
        return Category::create([
            'user_id' => $userId,
            'label' => $data['label'],
            'color' => $this->normalizeColor($data['color']),
            'is_system' => false,
        ]);
    }

    public function update(int $categoryId, int $userId, array $data): Category
    {
        $category = $this->findOwned($categoryId, $userId);

        $category->fill([
            'label' => $data['label'],
            'color' => $this->normalizeColor($data['color']),
        ])->save();

        return $category;
    }

    public function delete(int $categoryId, int $userId): void
    {
        $category = $this->findOwned($categoryId, $userId);

        $goalsCount = $category->goals()->count();
        if ($goalsCount > 0) {
            throw new DomainException(
                "Нельзя удалить категорию: в ней {$goalsCount} ".
                trans_choice('цель|цели|целей', $goalsCount).
                '. Сначала перенесите цели в другую категорию или удалите их.'
            );
        }

        $category->delete();
    }

    /**
     * Возвращает доступную пользователю категорию или 403/404.
     * Используется при валидации category_id для goals.
     */
    public function findAvailable(int $categoryId, int $userId): Category
    {
        $category = Category::find($categoryId);

        if (! $category) {
            abort(404);
        }

        if (! $category->is_system && $category->user_id !== $userId) {
            abort(403);
        }

        return $category;
    }

    private function findOwned(int $categoryId, int $userId): Category
    {
        $category = Category::find($categoryId);

        if (! $category) {
            abort(404);
        }

        if ($category->is_system) {
            throw new DomainException('Системные категории нельзя изменять или удалять.');
        }

        if ($category->user_id !== $userId) {
            abort(403);
        }

        return $category;
    }

    private function normalizeColor(string $color): string
    {
        $color = strtoupper(trim($color));

        if (! preg_match('/^#[0-9A-F]{6}$/', $color)) {
            throw new DomainException('Цвет должен быть в формате #RRGGBB.');
        }

        return $color;
    }
}
