<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    /**
     * Все категории, доступные пользователю: системные первыми,
     * потом его собственные (свежие сверху).
     */
    public function availableForUser(int $userId): Collection
    {
        return Category::query()
            ->availableTo($userId)
            ->orderByDesc('is_system')
            ->orderBy('id')
            ->orderByDesc('created_at')
            ->get();
    }

    public function ownedByUser(int $userId): Collection
    {
        return Category::query()
            ->ownedBy($userId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Уникальные hex-цвета пользовательских категорий — для
     * секции «Твои цвета» в форме создания/редактирования.
     */
    public function userColors(int $userId): array
    {
        return Category::query()
            ->ownedBy($userId)
            ->orderByDesc('created_at')
            ->pluck('color')
            ->unique()
            ->values()
            ->all();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function create(array $attrs): Category
    {
        return Category::create($attrs);
    }

    public function save(Category $category): Category
    {
        $category->save();

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function goalsCount(Category $category): int
    {
        return $category->goals()->count();
    }
}
