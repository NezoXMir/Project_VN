<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class AdminCategoryService
{
    public function __construct(private readonly CategoryRepository $repo)
    {
    }

    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Category::query()
            ->withCount('goals')
            ->with('user:id,name,email')
            ->orderByDesc('is_system')
            ->orderBy('id');

        if (! empty($filters['search'])) {
            $query->where('label', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['type'])) {
            if ($filters['type'] === 'system') {
                $query->where('is_system', true);
            } elseif ($filters['type'] === 'user') {
                $query->where('is_system', false);
            }
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): Category
    {
        return $this->repo->findById($id) ?? abort(404);
    }

    public function create(User $actor, array $data): Category
    {
        Gate::forUser($actor)->authorize('staffCreate', Category::class);

        return $this->repo->create([
            'user_id'   => null,
            'label'     => $data['label'],
            'color'     => $this->normalizeColor($data['color']),
            'is_system' => true,
        ]);
    }

    public function update(User $actor, int $id, array $data): Category
    {
        $category = $this->find($id);
        Gate::forUser($actor)->authorize('staffUpdate', $category);

        $category->label = $data['label'];
        $category->color = $this->normalizeColor($data['color']);

        return $this->repo->save($category);
    }

    public function delete(User $actor, int $id): void
    {
        $category = $this->find($id);
        Gate::forUser($actor)->authorize('staffDelete', $category);

        $goalsCount = $this->repo->goalsCount($category);
        if ($goalsCount > 0) {
            throw new DomainException(
                "Нельзя удалить категорию: к ней привязано {$goalsCount} " .
                ($goalsCount === 1 ? 'цель' : ($goalsCount < 5 ? 'цели' : 'целей')) . '.'
            );
        }

        $this->repo->delete($category);
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
