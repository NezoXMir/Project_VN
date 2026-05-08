<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ProfileService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profile)
    {
    }

    public function index(): View
    {
        return view('profile.index', [
            'user' => Auth::user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $this->profile->updateProfile(
            Auth::user(),
            $request->safe()->only(['name', 'email', 'bio']),
            $request->file('avatar'),
        );

        return redirect()->route('profile.index')->with('success', 'Профиль обновлён.');
    }

    public function updatePassword(PasswordChangeRequest $request): RedirectResponse
    {
        try {
            $this->profile->changePassword(
                Auth::user(),
                $request->string('current_password'),
                $request->string('password'),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['current_password' => $e->getMessage()]);
        }

        return redirect()->route('profile.index')->with('success', 'Пароль изменён.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(
            ['confirm_password' => ['required', 'string']],
            ['confirm_password.required' => 'Подтвердите паролем.'],
        );

        try {
            $this->profile->deleteAccount(Auth::user(), $request->string('confirm_password'));
        } catch (DomainException $e) {
            return back()->withErrors(['confirm_password' => $e->getMessage()]);
        }

        return redirect('/login')->with('success', 'Аккаунт удалён.');
    }
}
