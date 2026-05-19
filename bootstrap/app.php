<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);

        $middleware->alias([
            'auth' => \App\Http\Middleware\RequireAuth::class,
            'admin' => \App\Http\Middleware\RequireAdmin::class,
            'staff' => \App\Http\Middleware\RequireStaff::class,
            'user.only' => \App\Http\Middleware\ForbidStaffFromUserUi::class,
            'not.blocked' => \App\Http\Middleware\BlockBannedUsers::class,
        ]);

        // На каждый авторизованный запрос проверяем, не заблокирован ли юзер.
        // Если в БД проставили blocked_at — следующий же его запрос выкинет
        // на /login. Это страховка на случай, если админ блокирует уже
        // залогиненного пользователя.
        $middleware->appendToGroup('web', \App\Http\Middleware\BlockBannedUsers::class);

        // API-эндпоинты используют session-cookie auth, но не нуждаются
        // в CSRF: HTML-форм у них нет, доступ только из same-origin
        // или авторизованного клиента. Стандартный паттерн для Laravel API.
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
