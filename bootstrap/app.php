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
        $middleware->alias([
            'auth' => \App\Http\Middleware\RequireAuth::class,
            'admin' => \App\Http\Middleware\RequireAdmin::class,
        ]);

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
