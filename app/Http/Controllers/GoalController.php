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
        return view('goals.index', [
            'goals' => $this->goals->listForUser((int) Auth::id()),
        ]);
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
        $model = $this->goals->get($goal, (int) Auth::id());

        return view('goals.show', [
            'goal' => $model,
        ]);
    }

    public function edit(int $goal): View
    {
        $model = $this->goals->get($goal, (int) Auth::id());

        return view('goals.edit', [
            'goal' => $model,
            'categories' => $this->categories->listAvailable((int) Auth::id()),
        ]);
    }

    public function update(GoalRequest $request, int $goal): RedirectResponse
    {
        $this->goals->update($goal, (int) Auth::id(), $request->validated());

        return redirect()
            ->route('goals.show', $goal)
            ->with('success', 'Цель обновлена.');
    }

    public function destroy(int $goal): RedirectResponse
    {
        $this->goals->delete($goal, (int) Auth::id());

        return redirect()
            ->route('goals.index')
            ->with('success', 'Цель удалена.');
    }

    public function archive(int $goal): RedirectResponse
    {
        $this->goals->archive($goal, (int) Auth::id());

        return redirect()
            ->route('goals.show', $goal)
            ->with('success', 'Цель отправлена в архив.');
    }

    public function complete(int $goal): RedirectResponse
    {
        $userId = (int) Auth::id();
        $this->goals->complete($goal, $userId);
        $unlocked = $this->achievements->check($userId);

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
