<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminCategoryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly AdminCategoryService $service)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'type', 'user_id']);
        $categories = $this->service->paginate($filters);

        return view('admin.categories.index', compact('categories', 'filters'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:60',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        try {
            $this->service->create($request->user(), $data);
        } catch (DomainException $e) {
            return back()->withErrors(['color' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Системная категория создана.');
    }

    public function edit(int $id): View
    {
        $category = $this->service->find($id);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:60',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        try {
            $this->service->update($request->user(), $id, $data);
        } catch (DomainException $e) {
            return back()->withErrors(['color' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория обновлена.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        try {
            $this->service->delete($request->user(), $id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория удалена.');
    }
}
