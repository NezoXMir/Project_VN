<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoalRequest;
use App\Services\AchievementService;
use App\Services\CategoryService;
use App\Services\GoalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function __construct(
        private readonly GoalService $goals,
        private readonly CategoryService $categories,
        private readonly AchievementService $achievements,
    ) {
    }

    public function index(): View
    {
        $goals = $this->goals->listForUser((int) Auth::id())
            ->filter(fn ($g) => $g->status !== \App\Models\Goal::STATUS_ARCHIVED)
            ->values();

        return view('goals.index', compact('goals'));
    }

    public function create(): View
    {
        return view('goals.create', [
            'categories' => $this->categories->listAvailable((int) Auth::id()),
        ]);
    }

    public function store(GoalRequest $request): RedirectResponse
    {
        $goal = $this->goals->create((int) Auth::id(), $request->validated());

        return redirect()
            ->route('goals.show', $goal->id)
            ->with('success', 'Цель создана.');
    }

    public function show(int $goal): View
    {
        return view('goals.show', [
            'goal' => $this->goals->get($goal, Auth::user()),
        ]);
    }

    public function edit(int $goal): View
    {
        return view('goals.edit', [
            'goal' => $this->goals->get($goal, Auth::user()),
            'categories' => $this->categories->listAvailable((int) Auth::id()),
        ]);
    }

    public function update(GoalRequest $request, int $goal): RedirectResponse
    {
        $this->goals->update($goal, Auth::user(), $request->validated());

        return redirect()
            ->route('goals.show', $goal)
            ->with('success', 'Цель обновлена.');
    }

    public function destroy(int $goal): RedirectResponse
    {
        $this->goals->delete($goal, Auth::user());

        return redirect()
            ->route('goals.index')
            ->with('success', 'Цель удалена.');
    }

    public function archive(int $goal): RedirectResponse
    {
        $this->goals->archive($goal, Auth::user());

        return redirect()
            ->route('goals.show', $goal)
            ->with('success', 'Цель отправлена в архив.');
    }

    public function archiveIndex(): View
    {
        $goals = $this->goals->listForUser((int) Auth::id())
            ->filter(fn ($g) => $g->status === \App\Models\Goal::STATUS_ARCHIVED)
            ->values();

        return view('goals.archive', compact('goals'));
    }

    public function restore(int $goal): RedirectResponse
    {
        $this->goals->restore($goal, Auth::user());

        return redirect()
            ->route('goals.archive')
            ->with('success', 'Цель восстановлена.');
    }

    public function complete(int $goal): RedirectResponse
    {
        $user = Auth::user();
        $this->goals->complete($goal, $user);
        $unlocked = $this->achievements->check($user->id);

        $message = 'Цель отмечена как завершённая.';
        if (! empty($unlocked)) {
            $names = implode(', ', array_map(fn ($a) => $a->icon.' '.$a->name, $unlocked));
            $message .= " Получено достижение: {$names}";
        }

        return redirect()
            ->route('goals.show', $goal)
            ->with('success', $message);
    }
}
