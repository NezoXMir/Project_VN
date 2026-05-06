<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => $this->users->list(),
        ]);
    }

    public function updateRole(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'in:user,admin'],
        ], [], [
            'role' => 'роль',
        ]);

        try {
            $user = $this->users->changeRole($id, $data['role'], (int) $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Роль пользователя {$user->email} обновлена.");
    }
}
