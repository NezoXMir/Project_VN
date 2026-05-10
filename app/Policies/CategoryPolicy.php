<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Видеть категорию может: владелец её, ИЛИ любой
     * пользователь — если категория системная.
     */
    public function view(User $user, Category $category): bool
    {
        if ($category->is_system) {
            return true;
        }

        return $user->id === $category->user_id;
    }

    /**
     * Системные категории не редактируются никем — это часть
     * схемы. Пользовательские — только владельцем.
     */
    public function update(User $user, Category $category): bool
    {
        if ($category->is_system) {
            return false;
        }

        return $user->id === $category->user_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
