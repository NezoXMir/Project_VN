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
}
