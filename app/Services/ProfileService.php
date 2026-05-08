<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /**
     * Обновляет name/email/bio + опционально аватар.
     * Если загружен новый аватар — старый файл удаляется.
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'bio' => $data['bio'] ?? null,
        ]);

        if ($avatar !== null) {
            // Удаляем старый файл, если был.
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            // Уникальное имя на user_id + время — не зависим от
            // оригинального имени файла (могут быть кириллица, пробелы).
            $ext = $avatar->extension() ?: 'jpg';
            $path = "avatars/u{$user->id}_".time().'.'.$ext;
            Storage::disk('public')->putFileAs(
                'avatars',
                $avatar,
                basename($path),
            );
            $user->avatar_path = $path;
        }

        $user->save();

        return $user;
    }

    /**
     * Меняет пароль с проверкой текущего.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new DomainException('Текущий пароль указан неверно.');
        }

        $user->password = Hash::make($newPassword);
        $user->save();
    }

    /**
     * Удаляет аккаунт после проверки пароля. Аватар чистим вручную,
     * остальные связи (категории/цели/подцели/задачи) уйдут каскадом
     * через FK CASCADE из миграций предыдущих этапов.
     */
    public function deleteAccount(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw new DomainException('Пароль указан неверно.');
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Логаут до удаления — иначе сессия указывает на снесённую запись.
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $user->delete();
    }
}
