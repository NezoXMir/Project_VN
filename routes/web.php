<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
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

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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

    // Подцели и задачи — JSON-эндпоинты для AJAX из goals/show.
    Route::post('/goals/{goal}/subtasks', [SubtaskController::class, 'store'])
        ->whereNumber('goal')->name('subtasks.store');
    Route::patch('/subtasks/{subtask}', [SubtaskController::class, 'update'])
        ->whereNumber('subtask')->name('subtasks.update');
    Route::delete('/subtasks/{subtask}', [SubtaskController::class, 'destroy'])
        ->whereNumber('subtask')->name('subtasks.destroy');

    Route::post('/subtasks/{subtask}/tasks', [TaskController::class, 'store'])
        ->whereNumber('subtask')->name('tasks.store');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])
        ->whereNumber('task')->name('tasks.update');
    Route::post('/tasks/{task}/toggle', [TaskController::class, 'toggle'])
        ->whereNumber('task')->name('tasks.toggle');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
        ->whereNumber('task')->name('tasks.destroy');

    Route::middleware('admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole'])
                ->whereNumber('id')
                ->name('users.update-role');
        });
});
