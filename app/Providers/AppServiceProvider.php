<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Inject unread notifications into the app layout so every user page
        // can render the navbar bell without each controller passing it manually.
        View::composer('layouts.app', function ($view) {
            if (auth()->check()) {
                $view->with(
                    'navUnreadNotifications',
                    auth()->user()->unreadNotifications()->latest()->take(5)->get()
                );
            } else {
                $view->with('navUnreadNotifications', collect());
            }
        });
    }
}
