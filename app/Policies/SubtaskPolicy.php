<?php

namespace App\Policies;

use App\Models\Subtask;
use App\Models\User;

class SubtaskPolicy
{
    /**
     * Подцель доступна владельцу её цели — цепочка
     * Subtask → Goal → user_id.
     */
    public function view(User $user, Subtask $subtask): bool
    {
        return $user->id === $subtask->goal->user_id;
    }

    public function update(User $user, Subtask $subtask): bool
    {
        return $this->view($user, $subtask);
    }

    public function delete(User $user, Subtask $subtask): bool
    {
        return $this->view($user, $subtask);
    }
}
