<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profile)
    {
    }

    public function edit(): View
    {
        return view('admin.profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
        ]);

        $this->profile->updateProfile(auth()->user(), $data);

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Данные обновлены.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'password.min'       => 'Новый пароль — минимум 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        try {
            $this->profile->changePassword(
                auth()->user(),
                $request->string('current_password'),
                $request->string('password'),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['current_password' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Пароль изменён.');
    }
}
