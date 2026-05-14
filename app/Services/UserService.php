<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Все мутации в этом сервисе — под транзакцией с `lockForUpdate`.
 * Защита от race condition особенно важна для проверки «последний админ» —
 * см. обоснование в docs/stage-03-log.md (Notes #1).
 */
class UserService
{
    /**
     * Возвращает список пользователей с применением фильтров.
     * Поддерживаемые ключи: `search`, `role`, `status` (active/blocked).
     */
    public function list(array $filters = []): Collection
    {
        $query = User::query();

        if (! empty($filters['search'])) {
            $needle = '%'.trim($filters['search']).'%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            });
        }

        if (! empty($filters['role']) && in_array($filters['role'], [User::ROLE_USER, User::ROLE_MANAGER, User::ROLE_ADMIN], true)) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'blocked') {
                $query->whereNotNull('blocked_at');
            } elseif ($filters['status'] === 'active') {
                $query->whereNull('blocked_at');
            }
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function find(int $id): User
    {
        $user = User::find($id);
        if (! $user) {
            abort(404);
        }

        return $user;
    }

    /**
     * Создание пользователя из админки. `actor` — тот, кто создаёт
     * (для авторизации через Policy). Если в `$data['password']`
     * нет значения — генерируем сами, возвращаем пользователя
     * с виртуальным атрибутом `_generated_password` (флеш-показ).
     */
    public function create(User $actor, array $data): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $password = $data['password'] ?? $this->generatePassword();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'] ?? User::ROLE_USER,
            'password' => Hash::make($password),
        ]);

        // Виртуальный атрибут, чтобы контроллер мог показать пароль
        // в flash-сообщении один раз. Не сохраняется в БД.
        $user->_generated_password = $password;

        return $user;
    }

    /**
     * Обновление имени/email/роли. Изменение собственной роли запрещено.
     * Понижение последнего админа защищено count() под lockForUpdate.
     */
    public function update(int $id, User $actor, array $data): User
    {
        return DB::transaction(function () use ($id, $actor, $data) {
            $target = User::query()->lockForUpdate()->find($id);
            if (! $target) {
                abort(404);
            }

            Gate::forUser($actor)->authorize('update', $target);

            $newRole = $data['role'] ?? $target->role;

            if ($newRole !== $target->role) {
                if ($target->id === $actor->id) {
                    throw new DomainException('Нельзя изменить собственную роль.');
                }

                $this->ensureNotLastAdmin($target, $newRole);
            }

            $target->fill([
                'name' => $data['name'] ?? $target->name,
                'email' => $data['email'] ?? $target->email,
                'role' => $newRole,
            ]);
            $target->save();

            return $target;
        });
    }

    public function block(int $id, User $actor): User
    {
        return DB::transaction(function () use ($id, $actor) {
            $target = User::query()->lockForUpdate()->find($id);
            if (! $target) {
                abort(404);
            }

            Gate::forUser($actor)->authorize('block', $target);

            if ($target->blocked_at !== null) {
                return $target;
            }

            $target->blocked_at = Carbon::now();
            $target->save();

            return $target;
        });
    }

    public function unblock(int $id, User $actor): User
    {
        return DB::transaction(function () use ($id, $actor) {
            $target = User::query()->lockForUpdate()->find($id);
            if (! $target) {
                abort(404);
            }

            Gate::forUser($actor)->authorize('unblock', $target);

            $target->blocked_at = null;
            $target->save();

            return $target;
        });
    }

    public function delete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor) {
            $target = User::query()->lockForUpdate()->find($id);
            if (! $target) {
                abort(404);
            }

            Gate::forUser($actor)->authorize('delete', $target);

            if ($target->id === $actor->id) {
                throw new DomainException('Нельзя удалить собственный аккаунт.');
            }

            // Понижение в роли = удаление: если это последний admin —
            // запрет с той же логикой.
            if ($target->isAdmin()) {
                $this->ensureNotLastAdmin($target, User::ROLE_USER);
            }

            $target->delete();
        });
    }

    /**
     * Случайный пароль из заглавных, строчных букв и цифр.
     * Длина по умолчанию — 8 символов (по требованию задания).
     */
    public function generatePassword(int $length = 8): string
    {
        // Str::random хоть и криптостойкий, но даёт base64 (+/=).
        // Нам нужен пароль из ascii-letters+digits для удобного диктования.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $maxIdx = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $maxIdx)];
        }

        return $out;
    }

    /**
     * Кидает DomainException, если изменение/удаление приведёт
     * к нулю администраторов. Вызывать только под транзакцией
     * с lockForUpdate на админах — иначе возможен race.
     */
    private function ensureNotLastAdmin(User $target, string $newRole): void
    {
        if (! $target->isAdmin() || $newRole === User::ROLE_ADMIN) {
            return;
        }

        $adminCount = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->lockForUpdate()
            ->count();

        if ($adminCount <= 1) {
            throw new DomainException('Нельзя оставить систему без администратора.');
        }
    }
}
