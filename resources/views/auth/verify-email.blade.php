@extends('layouts.guest')

@section('title', 'Подтверждение email — ' . config('app.name'))

@section('content')
    <div class="text-center mb-6">
        <div class="text-5xl mb-3">📧</div>
        <h2 class="text-xl font-semibold mb-1">Подтвердите email</h2>
        <p class="text-sm text-gray-500">
            Письмо отправлено на
            <span class="font-medium text-gray-700">{{ auth()->user()->email }}</span>
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Ввод кода --}}
    <form method="POST" action="{{ route('email.verify.code') }}" class="mb-5">
        @csrf
        <label class="block text-sm font-medium text-gray-700 mb-2 text-center">
            Введите 6-значный код из письма
        </label>
        <input type="text"
               name="code"
               inputmode="numeric"
               pattern="[0-9]{6}"
               maxlength="6"
               placeholder="000000"
               autofocus
               value="{{ old('code') }}"
               class="w-full text-center text-3xl font-bold tracking-[0.4em] rounded-lg border-gray-300
                      focus:border-indigo-500 focus:ring-indigo-500 px-3 py-3 border mb-3">
        <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-lg transition">
            Подтвердить
        </button>
    </form>

    <div class="flex items-center gap-3 text-xs text-gray-400 mb-5">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span>или нажмите ссылку из письма</span>
        <div class="flex-1 h-px bg-gray-200"></div>
    </div>

    {{-- Повторная отправка --}}
    <form method="POST" action="{{ route('email.verification.resend') }}" class="mb-3">
        @csrf
        <button type="submit"
                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-4 py-2.5 rounded-lg transition text-sm">
            Отправить письмо повторно
        </button>
    </form>

    {{-- Пропустить --}}
    <a href="{{ route('dashboard') }}"
       class="block w-full text-center text-sm text-gray-400 hover:text-gray-600 py-2 transition">
        Продолжить без подтверждения →
    </a>
@endsection
