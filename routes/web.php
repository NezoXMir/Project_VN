<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\GoalApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Support\HomePath;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect('/login');
    }

    // Решение «куда отправить» вынесено в HomePath, чтобы любой код,
    // дёргающий «домашнюю страницу», не дублировал if isStaff().
    return redirect(HomePath::for(Auth::user()));
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

    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');

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

    // Demo REST API — тот же session-cookie auth, что и веб-интерфейс
    Route::prefix('api')->group(function () {
        Route::get('/goals', [GoalApiController::class, 'index'])->name('api.goals.index');
        Route::post('/goals', [GoalApiController::class, 'store'])->name('api.goals.store');
        Route::get('/goals/{goal}', [GoalApiController::class, 'show'])
            ->whereNumber('goal')
            ->name('api.goals.show');
        Route::get('/user/stats', [GoalApiController::class, 'userStats'])->name('api.user.stats');
    });

    // Группа admin/* открыта для admin и manager (middleware 'staff').
    // Разграничение возможностей внутри — на уровне Policy.
    Route::middleware('staff')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            // Управление пользователями
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::get('/users/{id}', [AdminUserController::class, 'show'])
                ->whereNumber('id')->name('users.show');
            Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])
                ->whereNumber('id')->name('users.edit');
            Route::patch('/users/{id}', [AdminUserController::class, 'update'])
                ->whereNumber('id')->name('users.update');
            Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])
                ->whereNumber('id')->name('users.destroy');
            Route::post('/users/{id}/block', [AdminUserController::class, 'block'])
                ->whereNumber('id')->name('users.block');
            Route::post('/users/{id}/unblock', [AdminUserController::class, 'unblock'])
                ->whereNumber('id')->name('users.unblock');
            // legacy: точечное изменение роли — оставлено для совместимости
            Route::patch('/users/{id}/role', [AdminUserController::class, 'updateRole'])
                ->whereNumber('id')->name('users.update-role');
        });
});
