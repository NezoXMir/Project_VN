<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Задача доступна владельцу её цели — цепочка
     * Task → Subtask → Goal → user_id.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->subtask->goal->user_id;
    }

    public function update(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function toggle(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }
}
