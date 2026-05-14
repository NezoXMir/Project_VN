<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Если staff (admin/manager) попадает на пользовательский маршрут —
 * редиректит на админ-дашборд. Для JSON-запросов отдаёт 403 чтобы
 * Demo API корректно отвечал клиенту, а не страницей редиректа.
 */
class ForbidStaffFromUserUi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isStaff()) {
            if ($request->expectsJson()) {
                abort(403, 'Пользовательский интерфейс недоступен для административных аккаунтов.');
            }

            return redirect('/admin/dashboard');
        }

        return $next($request);
    }
}
