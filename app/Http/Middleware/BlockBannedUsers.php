<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Если у пользователя установлен blocked_at — выкидываем из системы.
 * Сессия инвалидируется, токен пересоздаётся — даже если злоумышленник
 * перехватил cookie, после блокировки она перестаёт работать.
 */
class BlockBannedUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Аккаунт заблокирован администратором.');
            }

            return redirect('/login')
                ->with('error', 'Ваш аккаунт заблокирован администратором.');
        }

        return $next($request);
    }
}
