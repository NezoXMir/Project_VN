<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function list(): Collection
    {
        return User::query()
            ->orderByDesc('created_at')
            ->get();
    }

    public function changeRole(int $id, string $role, int $actorId): User
    {
        if (! in_array($role, [User::ROLE_USER, User::ROLE_ADMIN], true)) {
            throw new DomainException('Недопустимая роль.');
        }

        if ($id === $actorId) {
            throw new DomainException('Нельзя изменить собственную роль.');
        }

        return DB::transaction(function () use ($id, $role) {
            $user = User::query()->lockForUpdate()->findOrFail($id);

            if ($user->role === $role) {
                return $user;
            }

            if ($user->isAdmin() && $role === User::ROLE_USER) {
                $adminCount = User::query()
                    ->where('role', User::ROLE_ADMIN)
                    ->lockForUpdate()
                    ->count();

                if ($adminCount <= 1) {
                    throw new DomainException('Нельзя понизить последнего администратора.');
                }
            }

            $user->role = $role;
            $user->save();

            return $user;
        });
    }
}
