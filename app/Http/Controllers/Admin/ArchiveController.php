<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminGoalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function __construct(private readonly AdminGoalService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'user_id']);
        $filters['status'] = 'archived';

        $goals = $this->service->paginate($filters);

        return view('admin.archive.index', compact('goals', 'filters'));
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $this->service->staffRestore($request->user(), $id);

        return back()->with('success', 'Цель восстановлена из архива.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->service->staffDelete($request->user(), $id);

        return redirect()->route('admin.archive.index')
            ->with('success', 'Цель удалена безвозвратно.');
    }
}
