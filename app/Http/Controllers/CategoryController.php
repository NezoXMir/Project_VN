<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Services\CategoryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Палитра пресетов для UI. Пользователь может выбрать любой из них
     * либо ввести произвольный hex через color picker.
     */
    public const PALETTE = [
        '#4F46E5', // indigo
        '#10B981', // emerald
        '#F59E0B', // amber
        '#EF4444', // red
        '#0EA5E9', // sky
        '#8B5CF6', // violet
        '#EC4899', // pink
        '#64748B', // slate
    ];

    public function __construct(private readonly CategoryService $categories)
    {
    }

    public function index(): View
    {
        $userId = (int) Auth::id();

        return view('categories.index', [
            'all' => $this->categories->listAvailable($userId),
        ]);
    }

    public function create(): View
    {
        return view('categories.create', [
            'palette' => self::PALETTE,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        try {
            $this->categories->create((int) Auth::id(), $request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Категория создана.');
    }

    public function edit(int $category): View
    {
        $model = $this->categories->findAvailable($category, (int) Auth::id());

        if ($model->is_system) {
            abort(403, 'Системные категории нельзя редактировать.');
        }

        return view('categories.edit', [
            'category' => $model,
            'palette' => self::PALETTE,
        ]);
    }

    public function update(CategoryRequest $request, int $category): RedirectResponse
    {
        try {
            $this->categories->update($category, (int) Auth::id(), $request->validated());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Категория обновлена.');
    }

    public function destroy(int $category): RedirectResponse
    {
        try {
            $this->categories->delete($category, (int) Auth::id());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Категория удалена.');
    }
}
