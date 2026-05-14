<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    /**
     * Просмотр и любые мутации цели разрешены только её владельцу.
     * Админы намеренно НЕ имеют доступа к чужим целям —
     * см. README, раздел «Без доступа к персональным данным».
     */
    public function view(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    public function update(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    public function archive(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    public function complete(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    /**
     * Пользователь может восстановить только свою архивную цель.
     */
    public function restore(User $user, Goal $goal): bool
    {
        return $user->id === $goal->user_id;
    }

    /* ------------------------- staff-уровень ----------------------------- */
    /*
     * Эти ability нужны только админ-разделу. Они нарочно отделены от
     * owner-методов выше, чтобы изменения политики staff не задевали
     * пользовательские проверки. В Blade использовать как
     * @can('staffArchive', $goal).
     */

    public function staffView(User $user, Goal $goal): bool
    {
        return $user->isStaff();
    }

    public function staffArchive(User $user, Goal $goal): bool
    {
        return $user->isStaff();
    }

    public function staffRestore(User $user, Goal $goal): bool
    {
        return $user->isStaff();
    }

    /**
     * Жёсткое удаление чужой цели — только для админа. Менеджер
     * максимум может архивировать.
     */
    public function staffDelete(User $user, Goal $goal): bool
    {
        return $user->isAdmin();
    }
}
