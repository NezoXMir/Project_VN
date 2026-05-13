<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $service) {}

    /** Страница с формой ввода кода. */
    public function show(): View|RedirectResponse
    {
        if (auth()->user()->isEmailVerified()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    /** Проверка 6-значного кода. */
    public function verifyCode(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        if (! $this->service->verifyByCode(auth()->user(), $request->code)) {
            return back()->withErrors(['code' => 'Неверный или истёкший код. Запросите новое письмо.']);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Email успешно подтверждён!');
    }

    /** Верификация по ссылке из письма (маршрут защищён подписью). */
    public function verifyLink(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! $user->isEmailVerified()) {
            $this->service->verifyByLink($user);
        }

        // Пользователь мог открыть ссылку в другом браузере — логиним.
        if (! Auth::check()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        return redirect()->route('dashboard')
            ->with('success', 'Email успешно подтверждён!');
    }

    /** Повторная отправка письма (из verify-страницы и из профиля). */
    public function resend(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isEmailVerified()) {
            return redirect()->route('dashboard');
        }

        $this->service->sendVerificationEmail($user);

        return redirect()->route('email.verify.notice')
            ->with('success', 'Новое письмо отправлено на ' . $user->email);
    }
}
