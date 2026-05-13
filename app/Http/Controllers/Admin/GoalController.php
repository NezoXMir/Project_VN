<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminGoalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function __construct(private readonly AdminGoalService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'user_id', 'overdue']);
        $goals = $this->service->paginate($filters);

        return view('admin.goals.index', compact('goals', 'filters'));
    }

    public function show(int $id): View
    {
        $goal = $this->service->findWithRelations($id);

        return view('admin.goals.show', compact('goal'));
    }

    public function archive(Request $request, int $id): RedirectResponse
    {
        $this->service->staffArchive($request->user(), $id);

        return back()->with('success', 'Цель перемещена в архив.');
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $this->service->staffRestore($request->user(), $id);

        return back()->with('success', 'Цель восстановлена из архива.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->service->staffDelete($request->user(), $id);

        return redirect()->route('admin.goals.index')
            ->with('success', 'Цель удалена безвозвратно.');
    }
}
