<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пропускает только администраторов и менеджеров — двух представителей
 * «административного интерфейса». Применяется ко всей группе /admin/*.
 * Разделение «что доступно admin, а что manager» — на уровне Policy.
 */
class RequireStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->isStaff()) {
            abort(403, 'Доступ только для администраторов и менеджеров.');
        }

        return $next($request);
    }
}
