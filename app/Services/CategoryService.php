<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class CategoryService
{
    public function __construct(private readonly CategoryRepository $repo)
    {
    }

    public function listAvailable(int $userId): Collection
    {
        return $this->repo->availableForUser($userId);
    }

    public function listOwn(int $userId): Collection
    {
        return $this->repo->ownedByUser($userId);
    }

    public function userColorsFor(int $userId): array
    {
        return $this->repo->userColors($userId);
    }

    public function create(int $userId, array $data): Category
    {
        return $this->repo->create([
            'user_id' => $userId,
            'label' => $data['label'],
            'color' => $this->normalizeColor($data['color']),
            'is_system' => false,
        ]);
    }

    public function update(int $categoryId, User $user, array $data): Category
    {
        $category = $this->findAuthorized($categoryId, $user, 'update');

        $category->fill([
            'label' => $data['label'],
            'color' => $this->normalizeColor($data['color']),
        ]);

        return $this->repo->save($category);
    }

    public function delete(int $categoryId, User $user): void
    {
        $category = $this->findAuthorized($categoryId, $user, 'delete');

        $goalsCount = $this->repo->goalsCount($category);
        if ($goalsCount > 0) {
            throw new DomainException(
                "Нельзя удалить категорию: в ней {$goalsCount} ".
                trans_choice('цель|цели|целей', $goalsCount).
                '. Сначала перенесите цели в другую категорию или удалите их.'
            );
        }

        $this->repo->delete($category);
    }

    /**
     * Возвращает доступную пользователю категорию или 403/404.
     * Используется при формировании select-вью с категориями.
     */
    public function findAvailable(int $categoryId, User $user): Category
    {
        return $this->findAuthorized($categoryId, $user, 'view');
    }

    private function findAuthorized(int $categoryId, User $user, string $ability): Category
    {
        $category = $this->repo->findById($categoryId);

        if (! $category) {
            abort(404);
        }

        Gate::forUser($user)->authorize($ability, $category);

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
