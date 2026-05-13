<?php

namespace App\Policies;

use App\Models\User;

/**
 * Разграничение прав admin vs manager на сущности User.
 *
 *  admin   — может всё кроме самоудаления / самопонижения.
 *  manager — может смотреть список и блокировать обычных юзеров.
 *  user    — никакого доступа к админ-разделу.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isStaff();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->isStaff();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->isAdmin();
    }

    public function block(User $actor, User $target): bool
    {
        if (! $actor->isStaff()) {
            return false;
        }

        if ($actor->id === $target->id) {
            return false;
        }

        // Admin блокирует кого угодно (кроме себя). Manager только
        // обычных пользователей — других staff он трогать не может.
        if ($actor->isAdmin()) {
            return true;
        }

        return ! $target->isStaff();
    }

    public function unblock(User $actor, User $target): bool
    {
        return $this->block($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        if (! $actor->isAdmin()) {
            return false;
        }

        return $actor->id !== $target->id;
    }
}
