<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoalRequest;
use App\Models\Goal;
use App\Services\GoalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function __construct(private readonly GoalService $goals)
    {
    }

    public function index(): View
    {
        $userId = (int) Auth::id();

        return view('goals.index', [
            'goals' => $this->goals->listForUser($userId),
            'categories' => Goal::CATEGORIES,
        ]);
    }

    public function create(): View
    {
        return view('goals.create', [
            'categories' => Goal::CATEGORIES,
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
            'categories' => Goal::CATEGORIES,
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
        $this->goals->complete($goal, (int) Auth::id());

        return redirect()
            ->route('goals.show', $goal)
            ->with('success', 'Цель отмечена как завершённая.');
    }
}
