<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use App\Support\HomePath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $this->verifyCaptcha($request);

        $this->auth->register($request->validated());

        // Регистрация всегда создаёт обычного пользователя — staff
        // заводится только админом. Поэтому жёстко на /dashboard.
        return redirect()->intended('/dashboard')
            ->with('success', 'Регистрация прошла успешно. Добро пожаловать!');
    }

    private function verifyCaptcha(Request $request): void
    {
        $token = (string) $request->input('smart-token', '');

        if ($token === '') {
            throw ValidationException::withMessages([
                'captcha' => 'Пройдите проверку капчи.',
            ]);
        }

        $response = Http::asForm()->post('https://smartcaptcha.yandexcloud.net/validate', [
            'secret' => config('services.yandex_captcha.secret'),
            'token'  => $token,
            'ip'     => $request->ip(),
        ]);

        if (! $response->successful() || $response->json('status') !== 'ok') {
            throw ValidationException::withMessages([
                'captcha' => 'Проверка капчи не пройдена. Попробуйте ещё раз.',
            ]);
        }
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $remember = $request->boolean('remember');

        if (! $this->auth->login($credentials, $remember)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Неверный email или пароль.']);
        }

        $user = Auth::user();

        // Заблокированный пользователь не должен зайти. AuthService
        // уже залогинил его — выкидываем сразу и стираем сессию.
        if ($user && $user->isBlocked()) {
            $this->auth->logout();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Аккаунт заблокирован администратором.']);
        }

        return redirect()->intended(HomePath::for($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout();

        return redirect('/login')->with('success', 'Вы вышли из аккаунта.');
    }
}
