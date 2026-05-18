@extends('layouts.app')

@section('title', 'Новая категория — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Новая категория</h1>
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
