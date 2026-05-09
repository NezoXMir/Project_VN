@extends('layouts.app')

@section('title', 'Новая категория — ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Новая категория</h1>
        <a href="{{ route('categories.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            ← К списку
        </a>
    </div>

    <x-card padding="p-6" class="max-w-xl">
        @include('categories._form', [
            'action' => route('categories.store'),
            'method' => 'POST',
            'palette' => $palette,
            'userPalette' => $userPalette,
            'submitLabel' => 'Создать',
        ])
    </x-card>
@endsection
