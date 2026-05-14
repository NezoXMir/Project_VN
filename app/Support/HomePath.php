<?php

namespace App\Support;

use App\Models\User;

/**
 * Единая точка решения «куда отправить юзера после логина / при заходе на /».
 * Любые ссылки на «домашнюю страницу» (AuthController, web.php) идут через
 * этот хелпер — если завтра понадобится третья роль или landing-флаги,
 * правка в одном месте.
 */
class HomePath
{
    public static function for(?User $user): string
    {
        if ($user && $user->isStaff()) {
            return '/admin/dashboard';
        }

        return '/dashboard';
    }
}
