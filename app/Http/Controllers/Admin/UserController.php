<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserStoreRequest;
use App\Http\Requests\Admin\AdminUserUpdateRequest;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use App\Services\UserService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'role', 'status']);

        return view('admin.users.index', [
            'users' => $this->users->list($filters),
            'filters' => $filters,
        ]);
    }

    public function show(int $id): View
    {
        $user = $this->users->find($id);

        // Агрегаты для админ-показа: сколько целей в каждом статусе,
        // сколько задач выполнено всего. Намеренно без bio/avatar —
        // персональные данные защищены принципом «только агрегаты».
        $stats = [
            'goals_active' => Goal::where('user_id', $id)->where('status', Goal::STATUS_ACTIVE)->count(),
            'goals_completed' => Goal::where('user_id', $id)->where('status', Goal::STATUS_COMPLETED)->count(),
            'goals_archived' => Goal::where('user_id', $id)->where('status', Goal::STATUS_ARCHIVED)->count(),
            'tasks_done' => Task::query()
                ->where('is_done', true)
                ->whereExists(function ($q) use ($id) {
                    $q->select(DB::raw(1))
                        ->from('subtasks')
                        ->join('goals', 'goals.id', '=', 'subtasks.goal_id')
                        ->whereColumn('subtasks.id', 'tasks.subtask_id')
                        ->where('goals.user_id', $id);
                })
                ->count(),
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(AdminUserStoreRequest $request): RedirectResponse
    {
        try {
            $user = $this->users->create(Auth::user(), $request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Пароль показываем один раз, потому что в БД он уже хешированный
        // и больше нигде не отображается. Админ должен переписать.
        return redirect()
            ->route('admin.users.index')
            ->with('success', "Пользователь {$user->email} создан. Пароль (показывается один раз): {$user->_generated_password}");
    }

    public function edit(int $id): View
    {
        $user = $this->users->find($id);

        return view('admin.users.edit', compact('user'));
    }

    public function update(AdminUserUpdateRequest $request, int $id): RedirectResponse
    {
        try {
            $this->users->update($id, Auth::user(), $request->validated());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.users.show', $id)
            ->with('success', 'Данные пользователя обновлены.');
    }

    public function block(int $id): RedirectResponse
    {
        try {
            $user = $this->users->block($id, Auth::user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Пользователь {$user->email} заблокирован.");
    }

    public function unblock(int $id): RedirectResponse
    {
        try {
            $user = $this->users->unblock($id, Auth::user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Пользователь {$user->email} разблокирован.");
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->users->delete($id, Auth::user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Пользователь удалён.');
    }

    /**
     * Старый маршрут update-role оставлен для совместимости —
     * делегирует в полноценный update с минимальным payload.
     */
    public function updateRole(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,manager,admin'],
        ]);

        $target = $this->users->find($id);

        try {
            $this->users->update($id, Auth::user(), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => $data['role'],
            ]);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Роль пользователя {$target->email} обновлена.");
    }
}
