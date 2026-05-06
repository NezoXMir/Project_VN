<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GoalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect('/dashboard')
        : redirect('/login');
})->name('home');

Route::get('/healthz', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'Виртуальный наставник',
    ]);
})->name('healthz');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::resource('categories', CategoryController::class)
        ->except(['show'])
        ->whereNumber('category');

    Route::resource('goals', GoalController::class)
        ->whereNumber('goal');
    Route::post('/goals/{goal}/archive', [GoalController::class, 'archive'])
        ->whereNumber('goal')
        ->name('goals.archive');
    Route::post('/goals/{goal}/complete', [GoalController::class, 'complete'])
        ->whereNumber('goal')
        ->name('goals.complete');

    Route::middleware('admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole'])
                ->whereNumber('id')
                ->name('users.update-role');
        });
});
